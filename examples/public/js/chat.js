(function() {
    document.addEventListener("DOMContentLoaded", () => {

        const elLog = document.getElementById('log');
        const elStatus = document.getElementById('status');
        const elSend = document.getElementById('send');
        const elData = document.getElementById('data');

        /**
         * Add a new log message.
         * @param {string} msg - The message to add.
         */
        const log = (msg) => {
            elLog.insertAdjacentHTML('afterbegin', `${msg}<br />`);
        };

        // Connect to server
        let socket = null;
        let serverUrl = 'ws://127.0.0.1:8000/chat';
        if (window.MozWebSocket) {
            socket = new MozWebSocket(serverUrl);
        } else if (window.WebSocket) {
            socket = new WebSocket(serverUrl);
        }
        socket.binaryType = 'blob';

        /**
         * Called when connected to websocket server.
         * @param {Object} msg
         */
        socket.onopen = (msg) => {
            elStatus.classList.remove('offline');
            elStatus.classList.add('online');
            elStatus.innerText = 'connected';
        };

        /**
         * Called when receiving a message from websocket server.
         * @param {Object} msg
         */
        socket.onmessage = (msg) => {
            let response = JSON.parse(msg.data);
            log(response.data);
        };

        /**
         * Called when disconnected from websocket server.
         * @param {Object} msg
         */
        socket.onclose = (msg) => {
            elStatus.classList.remove('online');
            elStatus.classList.add('offline');
            elStatus.innerText = 'disconnected';
        };

        /**
         * Adds event listener to the status indicator.
         */
        elStatus.addEventListener('click', () => {
            socket.close();
        });

        /**
         * Adds event listener to the send button.
         */
        elSend.addEventListener('click', () => {
            let payload = {
                action: 'echo',
                data: elData.value
            };
            socket.send(JSON.stringify(payload));
        });

        /**
         * Adds event listener to data input, so message can be sent with enter key
         */
        elData.addEventListener('keyup', (evt) => {
            if (evt.code !== 'Enter') {
                return;
            }
            let payload = {
                action: 'echo',
                data: elData.value
            };
            socket.send(JSON.stringify(payload));
            elData.value = '';
        });
    });
}).call();