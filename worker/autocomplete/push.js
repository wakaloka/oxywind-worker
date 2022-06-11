const axios = require('axios').default;
const core = require('@actions/core');

(async () => {
    const worker_token = process.env['worker_token'];
    const route_push = process.env['route_push'];
    const uuid = process.env['uuid'];
    const run_id = process.env['run_id'];
    const entity = process.env['entity'];
    const run_status = process.env['run_status'];

    axios
        .post(route_push, {
            entity: entity,
            uuid: uuid,
            run_id: run_id,
            run_status: run_status,
        }, {
            headers: {
                'Worker-Token': worker_token
            }
        })
        .catch(error => {
            core.setFailed(error.message);
        });
})();