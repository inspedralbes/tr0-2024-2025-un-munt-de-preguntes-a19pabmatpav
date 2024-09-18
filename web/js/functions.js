let data = {};
fetch('http://localhost/tr0-2024-2025-un-munt-de-preguntes-a19pabmatpav/preguntes.json')
  .then (response => response.json())
  .then (fetchedData => {
    data = fetchedData
    mostrarPreguntas()
  })

let htmlStr='';
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
  }else{
    htmlStr = '<h1>has finalitzat les preguntes</h1>';
    //PONER AQUI BOTON CERRAR SESSION
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
//enviar a metodo :corregirPreguntas
  fetch("http://localhost/tr0.2024-2025-un-munt-de-preguntes-a19pabmatpav/back/controller.php ", {
    method: 'POST',
    action: 'corregirPreguntes',
    headers: {
      'Content-Type' : 'application/json'
    },
    body: JSON.stringify(datosAEnviar)
  })
  .then(response=> {
    if (!response.ok) {
      throw new Error('Error en la respuesta del servidor');
    }
    return response.text();
  })
  .then(data => {
    if (data === 'true') {
      console.log('Respuesta correcta:', data); 
    } else {
      console.log('Respuesta incorrecta o fallida:', data); 
    }
  })

  console.log(datosAEnviar);
  mostrarPreguntas();
}