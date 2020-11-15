(function() {
    document.addEventListener("DOMContentLoaded", () => {

        const elLog = document.getElementById('log');
        const elStatus = document.getElementById('status');
        const elClientList = document.getElementById('clientListSelect');
        const elClientCount = document.getElementById('clientCount');
        const elMaxClients = document.getElementById('maxClients');
        const elMaxConnections = document.getElementById('maxConnections');
        const elMaxRequetsPerMinute = document.getElementById('maxRequetsPerMinute');

        /**
         * Adds a new log message.
         * @param {string} msg
         */
        const log = (msg) => {
            return elLog.insertAdjacentHTML('afterbegin', `${msg}<br />`);
        };

        /**
         * Adds new info or warning message.
         * @param {Object} msgData
         * @returns {*}
         */
        const statusMsg = (msgData) => {
            switch (msgData.type) {
                case "info":
                    return log(msgData.text);
                case "warning":
                    return log(`<span class="warning">${msgData.text}</span>`);
            }
        };

        /**
         * Adds new client to the list (on connection).
         * @param {Object} data
         */
        const clientConnected = (data) => {
            elClientList.append(new Option(data.client, data.client));
            elClientCount.textContent = data.clientCount;
        };

        /**
         * Removes client from list when disconnected.
         * @param {Object} data
         */
        const clientDisconnected = (data) => {
            [...elClientList.options].some((option, index) => {
                if (option.value === data.client) {
                    elClientList.options[index].remove();
                    return true;
                }
            });
            elClientCount.textContent = data.clientCount;
        };

        /**
         * Updates server data.
         * @param {Object} serverinfo
         */
        const refreshServerinfo = (serverinfo) => {
            elClientCount.textContent = serverinfo.clientCount;
            elMaxClients.textContent = serverinfo.maxClients;
            elMaxConnections.textContent = serverinfo.maxConnectionsPerIp;
            elMaxRequetsPerMinute.textContent = serverinfo.maxRequetsPerMinute;
            for (let client in serverinfo.clients) {
                if (!serverinfo.clients.hasOwnProperty(client)) {
                    continue;
                }
                elClientList.append(new Option(client, client));
            }
        };

        /**
         * Indicate client activity by animating/blinking entry in clients-list.
         * @param {string} port
         */
        const clientActivity = (client) => {
            [...elClientList.options].some((option, index) => {
                if (option.value === client) {
                    elClientList.options[index].style.color = 'red';
                    setTimeout(() => {
                        if (elClientList.options[index]) {
                            elClientList.options[index].style.color = 'black';
                        }
                    }, 600);
                }
            });
        };

        // Connect to websocket server
        let socket = null;
        const serverUrl = 'ws://localhost:8000/status';
        if (window.MozWebSocket) {
            socket = new MozWebSocket(serverUrl);
        } else if (window.WebSocket) {
            socket = new WebSocket(serverUrl);
        }

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
         * Called when disconnected from websocket server.
         * @param {Object} msg
         */
        socket.onclose = (msg) => {
            elStatus.classList.remove('online');
            elStatus.classList.add('offline');
            elStatus.innerText = 'disconnected';
        };

        /**
         * Called when a message is received from websocket server.
         * @param {Object} msg
         * @returns {*}
         */
        socket.onmessage = (msg) => {
            let response = JSON.parse(msg.data);
            switch (response.action) {
                case "statusMsg":
                    return statusMsg(response.data);
                case "clientConnected":
                    return clientConnected(response.data);
                case "clientDisconnected":
                    return clientDisconnected(response.data);
                case "clientActivity":
                    return clientActivity(response.data);
                case "serverInfo":
                    return refreshServerinfo(response.data);
            }
        };

        /**
         * Add event listener to satus indicator.
         */
        elStatus.addEventListener('click', () => {
            return socket.close();
        });
    });
}).call();
