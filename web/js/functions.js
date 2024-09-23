let data = {};
fetch('http://localhost/tr0-2024-2025-un-munt-de-preguntes-a19pabmatpav/preguntes.json')
  .then(response => response.json())
  .then(fetchedData => {
    data = fetchedData
    mostrarPreguntas()
  })

let htmlStr = '';
let preguntaActual = 0;
let resposta = '';

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
      return response.text();
    })
    .then(data => {
      const esCorrecta = (data === 'true');
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
      } else {
        console.error('Error al reiniciar la sesión:', data.error);
      }
    })
    .catch(error => {
      console.error('Error al cerrar sesión:', error);
    });
}
