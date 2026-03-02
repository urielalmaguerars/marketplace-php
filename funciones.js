function comprarObjeto(nombre, precio) {
    localStorage.setItem('objetoCompra', JSON.stringify({ nombre, precio }));
    window.location.href = 'compra.html';
}