const fs = require('fs');
const axios = require('axios').default;
const core = require('@actions/core');

(async () => {
    const uuid = process.env['uuid'];
    const nonce = process.env['site_nonce'];
    const site_endpoint = process.env['site_endpoint'];

    fs.readFile('./autocomplete.json', 'utf8', (err, data) => {
        if (err) {
            core.setFailed(err.message);
            return;
        }

        axios
            .post(site_endpoint, {
                uuid: uuid,
                payload: Buffer.from(data).toString('base64'),
                action: 'autocomplete',
                status: 'done',
            }, {
                headers: {
                    'Worker-Nonce': nonce
                }
            })
            .catch(error => {
                core.setFailed(error.message);
            });
    });
})();