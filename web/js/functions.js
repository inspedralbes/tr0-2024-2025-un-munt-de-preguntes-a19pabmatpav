let data = {};
let htmlStr = '';
let preguntaActual = 0;
let resposta = '';
let estatDeLaPartida = {
  preguntasRespondidas: 0,
  respuestas: {}
};
let temporizadorID;
let segundos = 0; 
let action = {action: 'prepararPreguntes'};
let nombreUsuario = '';
let numeroPreguntas = 0;
let configuracionJuego = document.getElementById('configuracionJuego');
let iniciarJuegoBtn = document.getElementById('iniciarJuegoBtn');
let temporizadorElement = document.getElementById('containerTempo');
let containerPreguntes = document.getElementById('containerPreguntes');

iniciarJuegoBtn.addEventListener('click', () => {
  nombreUsuario = document.getElementById('nombreUsuario').value;
  numeroPreguntas = parseInt(document.getElementById('numeroPreguntas').value);

  if (nombreUsuario && numeroPreguntas > 0) {
      configuracionJuego.style.display = 'none';
      document.getElementById('partida').style.display = 'block';
      fetchPreguntas();
      localStorage.setItem("name", nombreUsuario);
  } else {
      alert('Por favor, introduce tu nombre y un número de preguntas válido.');
  }
});

function fetchPreguntas() {
  let action = { action: 'prepararPreguntes', cantidad: numeroPreguntas };

  fetch("./php/controller.php", {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json'
      },
      body: JSON.stringify(action)
  })
  .then(response => response.json())
  .then(fetchedData => {
      data = fetchedData;
      iniciarTemporizador();
      mostrarPreguntas();
  })
  .catch(error => {
      console.error('Error en la solicitud fetch o al parsear el JSON:', error);
  });
}

function iniciarTemporizador() {
  temporizadorID = setInterval(() => { 
      segundos++;
      temporizadorElement.textContent = segundos;
      if (segundos >= 30) {
          finalizarJuego(); 
      }
  }, 1000); 
}

function finalizarTemporizador() {
  clearInterval(temporizadorID); 
  segundos = 0; 
}

function estatPartida() {
  const containerEstat = document.getElementById('containerEstat');
  containerEstat.innerHTML = `
    <h3>Estat de la Partida</h3>
    <p>Respostes: ${estatDeLaPartida.preguntasRespondidas}</p>`;
}

function mostrarPreguntas() {
console.log(data);

  if (estatDeLaPartida.preguntasRespondidas < data.length) { 
    let pregunta = data[preguntaActual]; 
    let htmlStr = `<div id="containerPregunta"><h2>${pregunta.pregunta}</h2></div>`;

    htmlStr += `<div id="prevBtn"><button class="boton-lateral">⬅</button></div>`;
    htmlStr += `<div id="containerImagen"><img src="${pregunta.imatge}" alt="Imatge de la pregunta"></div>`;
    htmlStr += `<div id="postBtn"><button class="boton-lateral">➡</button></div>`;
    htmlStr += '<div id="containerRespuestas"><div class="respuestas-container" id="respuestasContainer">';
    pregunta.respostes.forEach(respuesta => { 
      htmlStr += `<button class="respuestaBtn">${respuesta}</button>`;
    });
    htmlStr += '</div></div>'; 

    containerPreguntes.innerHTML = htmlStr; 
    estatPartida();
      document.getElementById('prevBtn').addEventListener('click', () => cambiarPregunta(-1));
      document.getElementById('postBtn').addEventListener('click', () => cambiarPregunta(1));

      if (preguntaActual === 0) {
        prevBtn.style.display = 'none'; 
      } else {
        prevBtn.style.display = 'inline'; 
      }
  
      if (preguntaActual === data.length - 1) {
        postBtn.style.display = 'none'; 
      } else {
        postBtn.style.display = 'inline'; 
      }
      const respuestaBtns = document.querySelectorAll('.respuestaBtn');
        respuestaBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const respuestaUsuari = btn.textContent;
                EnviarResposta(pregunta.id_pregunta, respuestaUsuari);
            });
        });
  } else {
      const containerEstat = document.getElementById('containerEstat');
      containerEstat.style.display = 'none';
      let htmlStr = `<h1>has finalitzat les preguntes, ${nombreUsuario}</h1>`;
      finalizarTemporizador();
      htmlStr += '<button id="cerrarSesionBtn">Cerrar sesión y reiniciar</button>';
      containerPreguntes.innerHTML = htmlStr;
      document.getElementById('cerrarSesionBtn').addEventListener('click', cerrarSesion);
  }
}

function cambiarPregunta(direccion) {
  preguntaActual += direccion;

  if (preguntaActual < 0) {
    preguntaActual = 0;
  } else if (preguntaActual >= data.length) {
    preguntaActual = data.length - 1; 
  }
  mostrarPreguntas();
}

function EnviarResposta(preguntaId, respostaUsuari) {
  console.log(preguntaId, respostaUsuari);

  if (!estatDeLaPartida.respuestas[preguntaId]) {
    estatDeLaPartida.preguntasRespondidas++;
  }

  estatDeLaPartida.respuestas[preguntaId] = respostaUsuari;

  let arreglo = preguntaId + "+" + respostaUsuari;
  let datosAEnviar = {
    action: 'corregirPreguntes',
    respuestas: [arreglo]
  };

  fetch("./php/controller.php", {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(datosAEnviar)
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('Error en la respuesta del servidor');
      }
      return response.json();
    })
    .then(data => {
      const esCorrecta = data.success;
      if (esCorrecta) {
        console.log('Respuesta correcta');
      } else {
        console.log('Respuesta incorrecta');
      }
      
      if (estatDeLaPartida.preguntasRespondidas >= data.length) {
        mostrarMensajeFinal();
      } else {
        mostrarPreguntas();
      }
    })
    .catch(error => {
      console.error('Error en el envío de la respuesta:', error);
    });

  console.log(datosAEnviar);
}

function finalizarJuego() {
  clearInterval(temporizadorID);
  temporizadorElement.textContent = '';
  temporizadorElement.classList.add('hidden');
  temporizadorElement.style.display = 'none';
  containerPreguntes.innerHTML = `<h1>¡El tiempo ha finalizado, ${nombreUsuario}!</h1>`;
  containerPreguntes.innerHTML += `<p>Puntuación: ${estatDeLaPartida.preguntasRespondidas} de ${numeroPreguntas}</p>`;
  containerPreguntes.innerHTML += '<button onclick="cerrarSesion()">Cerrar sesión y reiniciar</button>';
}

function cerrarSesion() {
  fetch("./php/controller.php", {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ action: 'reinicializarSesion' })
  }).then(response => {
    if (!response.ok) {
      throw new Error('Error en la respuesta del servidor');
    }
    return response.json(); 
  })
    .then(data => {
      console.log('Respuesta del servidor:', data);
      if (data.success) {
        console.log('Sesión reiniciada correctamente');
        location.reload();
      } else {
        console.error('Error al reiniciar la sesión:', data.error);
      }
    })
    .catch(error => {
      console.error('Error al cerrar sesión:', error);
    });
}
