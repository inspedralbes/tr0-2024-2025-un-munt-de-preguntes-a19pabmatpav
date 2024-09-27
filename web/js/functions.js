let data = {};
let htmlStr = '';
let preguntaActual = 0;
let resposta = '';
let estatDeLaPartida = {
  preguntasRespondidas: 0
};
let segundos = 0; 
let action = {action: 'prepararPreguntes'};

//Fetch inicial
fetch('../back/controller.php', {
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

let temporizadorElement = document.getElementById('containerTempo');

function iniciarTemporizador() {
  temporizadorID = setInterval(() => { 
      segundos++;
      temporizadorElement.textContent = segundos;
      if (segundos >= 30) {
          finalizarJuego(); 
      }
  }, 1000); 
}

function finalizarJuego() {
  clearInterval(temporizadorID);
  temporizadorElement.textContent = '';
  temporizadorElement.classList.add('hidden');
  temporizadorElement.style.display = 'none';
  containerPreguntes.innerHTML = '<h1>¡El tiempo ha finalizado!</h1>';
  containerPreguntes.innerHTML += '<button onclick="cerrarSesion()">Cerrar sesión y reiniciar</button>';
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
      let htmlStr = `<h2>${pregunta.pregunta}</h2>`;
      htmlStr += `<button class="boton-lateral" onclick="cambiarPregunta(-1)">⬅</button>`;
      htmlStr += `<img src="${pregunta.imatge}" alt="Imatge de la pregunta">`;
      htmlStr += `<button class="boton-lateral" onclick="cambiarPregunta(1)">➡</button>`;

      htmlStr += '<div class="respuestas-container">';
      pregunta.respostes.forEach(respuesta => { 
          htmlStr += `<button onclick="EnviarResposta(${pregunta.id_pregunta}, '${respuesta}')">${respuesta}</button>`;
      });
      htmlStr += '</div>'; 

      containerPreguntes.innerHTML = htmlStr; 
      estatPartida();
  } else {
      let htmlStr = '<h1>has finalitzat les preguntes</h1>';
      htmlStr += '<button onclick="cerrarSesion()">Cerrar sesión y reiniciar</button>';
      containerPreguntes.innerHTML = htmlStr;
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

  let arreglo = preguntaId + "+" + respostaUsuari;
  let datosAEnviar = {
    action: 'corregirPreguntes',
    respuestas: [arreglo]
  };

  fetch("../back/controller.php", {
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
      estatDeLaPartida.preguntasRespondidas++;
      if (preguntaActual >= data.length - 1) {
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

function cerrarSesion() {
  fetch("../back/controller.php", {
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
