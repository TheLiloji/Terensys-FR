const form = document.getElementById('contact-form');
const message = document.getElementById('sended');

form.addEventListener('submit', async function() {
    message.classList.remove('hidden');
    setTimeout(() => {
        message.classList.add('hidden');
    }, "5000");
})