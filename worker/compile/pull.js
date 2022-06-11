const fs = require('fs');
const axios = require('axios').default;
const core = require('@actions/core');

(async () => {
    const uuid = process.env['uuid'];
    const worker_token = process.env['worker_token'];
    const route_pull = process.env['route_pull'];
    const entity = process.env['entity'];

    axios
        .post(route_pull, {
            entity: entity,
            uuid: uuid,
        }, {
            headers: {
                'Worker-Token': worker_token
            }
        })
        .then(response => {
            return response.data.data;
        })
        .then(data => {
            // mast the string for future output
            core.setSecret(data.site);
            core.setSecret(data.nonce);

            core.exportVariable('npm_package', data.package);
            core.exportVariable('site_endpoint', data.site);
            core.exportVariable('site_nonce', data.nonce);

            const preset = data.config;

            fs.writeFile('./preset.js', preset, error => {
                if (error) {
                    core.setFailed(error.message);
                }
            });

            const content = data.content;

            fs.writeFile('./content.html', content, error => {
                if (error) {
                    core.setFailed(error.message);
                }
            });

            const input = data.css;

            fs.writeFile('./input.css', input, error => {
                if (error) {
                    core.setFailed(error.message);
                }
            });
        })
        .catch(error => {
            core.setFailed(error.message);
        });
})();