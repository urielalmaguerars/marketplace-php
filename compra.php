<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago con PayPal</title>
    <!-- PayPal SDK con moneda en MXN -->
    <script src="https://www.paypal.com/sdk/js?client-id=ASRM-e3uFgfSVzwvI76vy9TftQbwYV7ru837PWgQMKSTuufnB7Kdp3Vw96pGjWkPrebgqic9xloSG66o&currency=MXN"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e1ebf1;
            text-align: center;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        #paypal-button-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Resumen de compra</h2>
    <div id="cartInfo"></div>
    <p id="totalPrice"></p>

    <!-- Botón de PayPal -->
    <div id="paypal-button-container"></div>
</div>

<script>
    let total = 0;

    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        let cart = JSON.parse(decodeURIComponent(params.get('cart')));

        if (cart && cart.length > 0) {
            let cartInfoHTML = cart.map(item => `<p><strong>${item.name}</strong> - Precio: $${item.price}</p>`).join('');
            total = cart.reduce((sum, item) => sum + item.price, 0);

            document.getElementById('cartInfo').innerHTML = cartInfoHTML;
            document.getElementById('totalPrice').innerHTML = `<strong>Total a pagar: $${total.toFixed(2)}</strong>`;
        } else {
            document.getElementById('cartInfo').innerHTML = "<p>No hay productos en el carrito.</p>";
            total = 0;
        }

        // Renderizar botón de PayPal
        paypal.Buttons({
            style: {
                color: 'blue',
                shape: 'pill',
                label: 'pay'
            },
            createOrder: function (data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: total.toFixed(2)
                        },
                        description: 'Pago del carrito de compras'
                    }]
                });
            },
            onApprove: function (data, actions) {
                return actions.order.capture().then(function (details) {
                    alert('¡Pago completado por ' + details.payer.name.given_name + '!');
                    // Redireccionar si quieres
                    window.location.href = 'home.php';
                });
            }
        }).render('#paypal-button-container');
    });
</script>

</body>
</html>
