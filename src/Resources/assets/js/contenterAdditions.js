
class ContenterAdditions {

    constructor(contenter, data) {
        this.contenter = contenter;

        // Example
        //this.inputUrl = this.contenter.getWithDefault(data, 'inputUrl', null);
        console.log('Contenter additions registered');
    }

    update(data) {
        // Do something with the data before sending
        console.log('Addition update', data);
        return data;
    }

    handleResult(content, status, ajaxNumber) {
        // Do something with the content
        console.log('Addition handleResult', content, status, ajaxNumber);
    }

    handleError(request, status, error) {
        // Do something with an error
        console.log('Addition handleError', request, status, error);
    }

    registerFields(contenter, listener, fields, type){
        // Do something when registering fields (add field type or something)
        console.log('Addition registerFields', contenter, listener, fields, type);
    }
}

global.ContenterAdditions = ContenterAdditions;
