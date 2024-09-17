let data = {};
fetch('preguntes.json')
  .then (response => response.json())
  .then (fetchedData => {
    data = fetchedData
    mostrarPreguntas()
  })

let htmlStr='';
let preguntaActual = 0;
let TotesRespostes = [];

function mostrarPreguntas() {
 
  if (preguntaActual < 10) {
    let pregunta = data.preguntes[preguntaActual];
        let htmlStr = `<h2>${pregunta.pregunta}</h2>`;
        htmlStr += `<img src="${pregunta.imatge}" alt="Imatge de la pregunta"><br>`;
        
        pregunta.respostes.forEach(resposta => {
            htmlStr += `<button onclick="EnviarResposta(${preguntaActual}, ${resposta.id})">${resposta.resposta}</button><br>`;
        });
        preguntaActual++;
        document.write(htmlStr);
        
  }else{
    htmlStr = '<h1>has finalitzat les preguntes</h1>';
    document.write(htmlStr);
  }
}

function EnviarResposta(preguntaId, respostaUsuari) {
  console.log(preguntaId, respostaUsuari);

  let arreglo = preguntaId + "+" + respostaUsuari;
  TotesRespostes.push(arreglo);

  let index = TotesRespostes.findIndex(respuesta => respuesta.startsWith(preguntaId + "+"));

  if (index !== -1) {
    TotesRespostes[index] = arreglo;
  } else {
    TotesRespostes.push(arreglo);
  }
  console.log(TotesRespostes);
}