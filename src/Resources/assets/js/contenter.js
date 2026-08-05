class Contenter{

    constructor(data) {
        this.url = data.url;
        this.statisticsBtn = this.getWithDefault(data, 'statisticsBtn', contenterGlobals.statisticsBtn);
        this.statisticsUrl = this.getWithDefault(data, 'statisticsUrl');
        this.statisticsActive = false;

        this.data = {};
        this.data.page = this.getWithDefault(data, 'page', contenterGlobals.page);
        this.data.perPage = this.getWithDefault(data, 'perPage', contenterGlobals.perPage);

        this.textFields = this.getWithDefault(data, 'textFields');
        this.selects = this.getWithDefault(data, 'selects');
        this.buttonGroups = this.getWithDefault(data, 'buttonGroups');
        this.checkboxes = this.getWithDefault(data, 'checkboxes');
        this.singleButtonGroups = this.getWithDefault(data, 'singleButtonGroups');
        this.customFields = this.getWithDefault(data, 'customFields');

        this.element = this.getWithDefault(data, 'element');

        this.onUpdateList = this.getWithDefault(data, 'onUpdateList');
        this.onSuccess = this.getWithDefault(data, 'onSuccess');
        this.initialize = this.getWithDefault(data, 'initialize');
        this.onError = this.getWithDefault(data, 'onError');

        this.contenterAdditions = typeof ContenterAdditions !== 'undefined' ? new ContenterAdditions(this, data) : null;

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
        if(null !== this.singleButtonGroups){
            contenterGlobals.registerFields(this, this.singleButtonGroups, 'single-btn-group');
        }
        if(null !== this.checkboxes){
            contenterGlobals.registerFields(this, this.checkboxes, 'checkbox');
        }
        if(null !== this.customFields){
            contenterGlobals.registerFields(this, this.customFields, 'custom')
        }

        this.update();
    }

    update(){
        let url = this.url;
        if(this.statisticsActive === true){
            url = this.statisticsUrl;
        }

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
        if(null !== this.singleButtonGroups){
            data = contenterGlobals.setData(data, this.singleButtonGroups, 'single-btn-group');
        }
        if(null !== this.checkboxes){
            data = contenterGlobals.setData(data, this.checkboxes, 'checkbox');
        }
        if(null !== this.customFields) {
            data = contenterGlobals.setData(data, this.customFields, 'custom');
        }

        if (typeof this.onUpdateList === "function") {
            data = this.onUpdateList(data);
        }
        if(this.contenterAdditions && typeof this.contenterAdditions.update !== "undefined"){
            data = this.contenterAdditions.update(data);
        }

        this.addLoader();
        let t = this;
        $.ajax({
            method: 'post',
            url: url,
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

        if(null !== this.statisticsUrl){
            let btn = $(this.statisticsBtn);
            content.find('.statistics-btn-container').append(btn);
            btn.on('click', function(){
                t.statisticsActive = true;
                t.update();
            });
        }else{
            content.find('.statistics-btn-container').remove();
        }

        let t = this;
        content.find('.contenter-per-page-select').on('change', function(){
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

        if(this.contenterAdditions && typeof this.contenterAdditions.handleResult !== "undefined"){
            this.contenterAdditions.handleResult(content, status, ajaxNumber);
        }

        if(typeof this.onSuccess === 'function'){
            this.onSuccess(this, response);
        }
        if(typeof contenterGlobals.initialize === 'function'){
            contenterGlobals.initialize(content);
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

        if(this.contenterAdditions && typeof this.contenterAdditions.handleError !== "undefined"){
            this.contenterAdditions.handleError(request, status, error);
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
    statisticsBtn: '<button class="btn btn-sm btn-warning">Statistieken</button>',
    onError: function(contenter, request, status, error){
        if(request.status === 419 && request.responseText.includes('CSRF token mismatch')){
            window.location.href = window.location.href;
        }
        contenter.element.html(request.responseText);
    },
    timer: 500,
    perPage: 25,
    page: 1,
    setData: function(data, fields, type){
        fields.each(function(){
            let name = $(this).attr('name');
            if(typeof name === "undefined" || name === ''){
                name = $(this).data('name');
            }
            let value = $(this).val();
            if(typeof value === "undefined" || (value === '' && typeof $(this).data('value') !== "undefined")){
                value = $(this).data('value');
            }
            if(type === 'checkbox'){
                if(''+$(this).data('value') === '0'){
                    delete data[name];
                    return;
                }
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
            case 'checkbox':
                contenterGlobals.registerCheckboxes(contenter, fields);
                return;
            case 'btn-group':
                contenterGlobals.registerButtonGroups(contenter, fields);
                return;
            case 'single-btn-group':
                contenterGlobals.registerSingleButtonGroups(contenter, fields);
                return;
        }

        if(contenter.contenterAdditions && typeof contenter.contenterAdditions.registerFields !== "undefined"){
            listener = contenter.contenterAdditions.registerFields(listener, fields, type);
            if(listener === 'return'){
                return;
            }
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
            let buttons = $(this).find('button');
            let active = $(this).data('active');
            let inactive = $(this).data('inactive');
            let values = $(this).data('value');
            buttons.each(function(){
                let value = $(this).data('value');
                values[value] = values[$(this).data('value')] === true || values[$(this).data('value')] === 'true';

                if(values[value]){
                    $(this).addClass(active);
                }else{
                    $(this).addClass(inactive);
                }
            });

            buttons.on('click', function(){
                contenter.data.page = 1;
                let value = $(this).data('value');
                let parent = $(this).parent();
                let values = parent.data('value');

                if(values[value]){
                    $(this).removeClass(active).addClass(inactive);
                }else{
                    $(this).removeClass(inactive).addClass(active);
                }
                values[value] = !values[value];
                parent.data('value', values);
                clearTimeout(contenter.timer);
                contenter.timer = setTimeout(function(){
                    contenter.update();
                }, contenterGlobals.timer);
            });
        });
    },
    registerSingleButtonGroups: function(contenter, groups){
        groups.each(function(){
            let buttons = $(this).find('button');
            let value = $(this).data('value');
            buttons.each(function(){
                let btnValue = $(this).data('value');

                if(value === btnValue){
                    $(this).css({
                        display: ''
                    });
                }else{
                    $(this).css({
                        display: 'none'
                    });
                }
            });

            buttons.on('click', function(){
                contenter.data.page = 1;

                let next = $(this).next();
                if(next.length === 0){
                    next = $(buttons[0]);
                }
                $(this).css({
                    display: 'none'
                });
                next.css({
                    display: ''
                });
                $(this).parent().data('value', next.data('value'));
                clearTimeout(contenter.timer);
                contenter.timer = setTimeout(function(){
                    contenter.update();
                }, contenterGlobals.timer);
            });
        });
    },
    registerCheckboxes: function(contenter, checkboxes){
        checkboxes.on('change', function(){
            if($(this).is(':checked')){
                $(this).data('value', 1);
            }else{
                $(this).data('value', 0);
            }
            clearTimeout(contenter.timer);
            contenter.timer = setTimeout(function(){
                contenter.update();
            }, contenterGlobals.timer);
        });
        checkboxes.each(function(){
            if($(this).is(':checked')){
                $(this).data('value', 1);
            }else{
                $(this).data('value', 0);
            }
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
