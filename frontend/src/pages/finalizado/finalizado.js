import '../../main.js';

const title = document.querySelector('#confirmation-title');
const message = document.querySelector('#confirmation-message');
const summary = document.querySelector('#confirmation-summary');
const orderId = new URLSearchParams(window.location.search).get('pedido');

if (orderId) {
  title.textContent = 'Pedido confirmado';
  message.textContent = 'O pagamento simulado foi aprovado e seu pedido foi registrado com sucesso.';
  summary.textContent = `Número do pedido: #${orderId}`;
} else {
  summary.textContent = 'Nenhum pedido foi informado nesta sessão.';
}
