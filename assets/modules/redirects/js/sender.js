class FormSender {
    options = {
        formWrapper: '.form-wrapper',
        submitBtn: '[type=submit]',
        errorClass: 'has-error',
        errorMessageElement: 'div',
        errorMessageClass: 'error',
        url: '',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
    };

    constructor(options = {}) {
        if (typeof options !== 'object') throw new Error('Wrong options');

        if (typeof options.headers === 'object') {
            Object.assign(this.options.headers, options.headers);
        }
        delete options.headers;

        ['onSuccess', 'onFail', 'onError'].forEach(param => {
            if(typeof options[param] !== 'function') {
                delete options[param];
            }
        })

        Object.assign(this.options, options);

        this.init();
    }

    init() {
        const wrappers = document.querySelectorAll(this.options.formWrapper);
        const that = this;
        wrappers.forEach(wrapper => {
            const form = wrapper.querySelector('form');
            if (form) {
                form.addEventListener('submit', that.submit.bind(that), false);
            }
        });
    };

    submit(e) {
        e.preventDefault();
        const that = this;
        const form = e.target;
        const data = new FormData(form);
        const wrapper = form.closest(this.options.formWrapper);
        that.beforeSubmit(form);
        data.set('_token', config.csrf);
        this.request(data, function (response) {
            if (response.status) {
                if(typeof that.options.onSuccess === 'function') that.options.onSuccess({
                    wrapper: wrapper,
                    data: data,
                    response: response,
                });
            } else {
                that.processErrors(response, form);
                if(typeof that.options.onFail === 'function') that.options.onSuccess({
                    wrapper: wrapper,
                    data: data,
                    response: response,
                });
            }
        }, function (error) {
            if(typeof that.options.onError === 'function') that.options.onError({
                wrapper: wrapper,
                data: data,
                error: error,
            })
        });
    };

    beforeSubmit(form) {
        const messages = form.getElementsByClassName(this.options.errorMessageClass);
        Array.from(messages).forEach(messageElement => messageElement.remove());
        const errorClass = this.options.errorClass;
        const fields = form.getElementsByClassName(errorClass);
        Array.from(fields).forEach(field => field.classList.remove(errorClass));
    };

    request(data, successCallback, errorCallback) {
        fetch(new Request(this.options.url, {
            method: 'post',
            credentials: 'same-origin',
            headers: Object.assign(this.options.headers, {
                Accept: 'application/json'
            }),
            body: data
        }))
            .then(response => {
                if (!response.ok) {
                    throw new Error();
                }
                return response.json();
            })
            .then(successCallback)
            .catch(errorCallback);
    };

    processErrors(response, form) {
        const that = this;
        if (Object.keys(response.errors ?? []).length > 0) {
            const fields = form.querySelectorAll('[data-field]');
            fields.forEach(field => {
                const fieldName = field.getAttribute('data-field');
                if (typeof response.errors[fieldName] !== 'undefined') {
                    field.classList.add(that.options.errorClass);
                    const errors = response.errors[fieldName];
                    for (const error in errors) {
                        const el = document.createElement(that.options.errorMessageElement);
                        el.className = that.options.errorMessageClass;
                        el.textContent = errors[error];
                        field.appendChild(el);
                    }
                }
            });
        }
    }
}
