let data = {};
let action = {action: 'prepararPreguntes'};
fetch('../back/controller.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(action)
})
.then(response => response.text()) 
.then(text => {
  console.log(text); 
  try {
    data = JSON.parse(text); 
    mostrarPreguntas();
  } catch (error) {
    console.error('Error al parsear el JSON:', error); 
  }
})
.catch(error => {
  console.error('Error en la solicitud fetch:', error);
});

let htmlStr = '';
let preguntaActual = 0;
let resposta = '';
let estatDeLaPartida = {
  preguntasRespondidas: -1
};

function estatPartida() {
  const containerEstat = document.getElementById('containerEstat');
  containerEstat.innerHTML = `
    <h3>Estat de la Partida</h3>
    <p>Preguntas resposes: ${estatDeLaPartida.preguntasRespondidas}</p>`;
}

function mostrarPreguntas() {

  if (preguntaActual < 10) {
    let pregunta = data.preguntes[preguntaActual];
    let htmlStr = `<h2>${pregunta.pregunta}</h2>`;
    htmlStr += `<img src="${pregunta.imatge}" alt="Imatge de la pregunta"><br>`;

    pregunta.respostes.forEach(resposta => {
      htmlStr += `<button onclick="EnviarResposta(${preguntaActual}, ${resposta.id})">${resposta.resposta}</button><br>`;
    });
    containerPreguntes.innerHTML = htmlStr;
    preguntaActual++;
    estatDeLaPartida.preguntasRespondidas++;
    estatPartida();
  } else {
    htmlStr = '<h1>has finalitzat les preguntes</h1>';
    htmlStr += '<button onclick="cerrarSesion()">Cerrar sesión y reiniciar</button>';
    containerPreguntes.innerHTML = htmlStr;
  }

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
      mostrarPreguntas();
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
