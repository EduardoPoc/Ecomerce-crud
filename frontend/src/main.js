const app = document.querySelector('#app');

if (app) {
  const title = document.createElement('h1');
  title.textContent = 'Além da Estante';

  const description = document.createElement('p');
  description.textContent = 'Uma livraria para descobrir histórias com calma.';

  app.append(title, description);
}
