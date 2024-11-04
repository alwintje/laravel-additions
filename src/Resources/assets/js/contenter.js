class Contenter{

    constructor(data) {
        this.url = data.url;
        this.data = {};
        this.data.page = this.getWithDefault(data, 'page', 1);
        this.data.perPage = this.getWithDefault(data, 'perPage', 25);
        this.textFields = this.getWithDefault(data, 'textFields');
        this.selects = this.getWithDefault(data, 'selects');
        this.buttonGroups = this.getWithDefault(data, 'buttonGroups');
        this.customFields = this.getWithDefault(data, 'customFields');
        this.element = this.getWithDefault(data, 'element');
        this.onUpdateList = this.getWithDefault(data, 'onUpdateList');
        this.onSuccess = this.getWithDefault(data, 'onSuccess');
        this.initialize = this.getWithDefault(data, 'initialize');
        this.onError = this.getWithDefault(data, 'onError');
        this.ajaxNumber = 0;
        this.timer = null;

        if(this.element === null){
            this.element = $('#content');
        }

        if(null !== this.textFields){
            contenterGlobals.registerFields(this, this.textFields, 'text');
        }
        if(null !== this.selects){
            contenterGlobals.registerFields(this, this.selects, 'select');
        }
        if(null !== this.buttonGroups){
            contenterGlobals.registerFields(this, this.buttonGroups, 'btn-group');
        }
        if(null !== this.customFields){
            contenterGlobals.registerFields(this, this.customFields, 'custom')
        }

        this.update();
    }

    update(){
        this.ajaxNumber++;
        let data = this.data;

        if(null !== this.textFields) {
            data = contenterGlobals.setData(data, this.textFields, 'text');
        }
        if(null !== this.selects){
            data = contenterGlobals.setData(data, this.selects, 'select');
        }
        if(null !== this.buttonGroups){
            data = contenterGlobals.setData(data, this.buttonGroups, 'btn-group');
        }
        if(null !== this.customFields) {
            data = contenterGlobals.setData(data, this.customFields, 'custom');
        }

        if (typeof this.onUpdateList === "function") {
            data = this.onUpdateList(data);
        }
        this.addLoader();
        let t = this;
        $.ajax({
            method: 'post',
            url: this.url,
            data: data,
            headers: {
                'Ajax-Number': this.ajaxNumber,
            },
            success: function(response, textStatus, jqXHR){
                let ajaxNumber = jqXHR.getResponseHeader('Ajax-Number');
                if (ajaxNumber != null) {
                    ajaxNumber = parseInt(ajaxNumber);
                }
                t.handleResult(response, 'success', ajaxNumber);
            },
            error: function (request, status, error){
                t.handleError(request, status, error);
            },
        });
    }

    handleResult(response, status, ajaxNumber){
        this.removeLoader();
        if (status !== 'success' || ajaxNumber !== this.ajaxNumber) {
            return
        }
        let content = this.element;
        content.html(response);

        let t = this;
        content.find('#per-page-select').on('change', function(){
            t.data.perPage = $(this).val();
            t.data.page = 1;
            t.update();
        });
        content.find('.pagination a').on('click', function(){
            let urlParts = $(this).attr('href').substring(2).split('&');
            for (let i = 0; i < urlParts.length; i++){
                let urlPart = urlParts[i].split('=');
                if(urlPart[0] === 'page'){
                    t.data.page = urlPart[1];
                }
                // data[urlPart[0]] = urlPart[1];
            }
            t.update();
            return false;
        });
        content.find('.contenter-sort').on('click', function(){
            let field = $(this).data('field');
            let direction = $(this).data('direction');
            t.data.page = 1;
            t.data.sorting = JSON.stringify({
                field: field,
                direction: direction,
            });
            t.update();
            return false;
        });

        if(typeof this.onSuccess === 'function'){
            this.onSuccess(this, response);
        }
        if(typeof this.initialize === 'function'){
            this.initialize(content);
        }

    }

    handleError(request, status, error){
        this.removeLoader();
        if(typeof this.onError === "function"){
            this.onError(this, request, status, error)
            return;
        }
        contenterGlobals.onError(this, request, status, error);
    }

    addLoader() {
        if(typeof this.element.data('html') === "undefined" || this.element.data('html') === null){
            this.element.data('html', this.element.html());
            this.element.html(contenterGlobals.loaderHTML);
        }
    }

    removeLoader() {
        this.element.html(this.element.data('html'));
        this.element.data('html', null);
    }

    getWithDefault(object, field, defaultValue) {
        if(typeof object[field] !== "undefined"){
            return object[field];
        }
        return typeof defaultValue !== "undefined" ? defaultValue : null;
    }
}

global.contenterGlobals = {
    loaderHTML: '<i class="fa fa-spin fa-spinner fa-3x"></i>',
    onError: function(contenter, request, status, error){
        console.log(request);
        contenter.element.html(request.responseText);
    },
    timer: 500,
    setData: function(data, fields, type){
        fields.each(function(){
            let name = $(this).attr('name');
            if(typeof name === "undefined"){
                name = $(this).data('name');
            }
            let value = $(this).val();
            if(typeof value === "undefined"){
                value = $(this).data('value');
            }
            data[name] = value;
        });
        return data;
    },
    registerFields: function(contenter, fields, type) {
        let listener = 'change';
        switch (type) {
            case 'text':
                listener = 'keyup';
                break;
            case 'select':
                listener = 'change';
                break;
            case 'btn-group':
                contenterGlobals.registerButtonGroups(contenter, fields);
                return;
        }
        fields.on(listener, function(){
            contenter.addLoader();
            clearTimeout(contenter.timer);
            contenter.timer = setTimeout(function(){
                contenter.update();
            }, contenterGlobals.timer);
        });
    },
    registerButtonGroups: function(contenter, groups){
        groups.each(function(){
            let values = {};
            $(this).find('.btn').each(function(){
                values[$(this).data('value')] = $(this).hasClass('btn-success');
            });
            $(this).data('value', values);
            $(this).find('.btn').on('click', function(){
                contenter.data.page = 1;
                let data = $(this).data('value');
                let parent = $(this).parent();
                let values = parent.data('value');
                values[data] = !values[data];
                if(values[data]){
                    $(this).addClass('btn-success').removeClass('btn-default');
                }else{
                    $(this).addClass('btn-default').removeClass('btn-success');
                }
                parent.data('value', values);
                clearTimeout(contenter.timer);
                contenter.timer = setTimeout(function(){
                    contenter.update();
                }, contenterGlobals.timer);
            });
        });
    }
}

jQuery.fn.contenter = function (data){
    let contenters = [];
    $(this).each(function(){
        data.element = $(this);
        contenters[contenters.length] = new Contenter(data);
    });

    if(contenters.length === 1){
        return contenters[0];
    }
    return contenters;
};

global.Contenter = Contenter;
