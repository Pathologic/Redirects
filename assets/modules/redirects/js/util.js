const sanitize = function (value) {
    if (typeof value === 'string') value = value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    return value;
}
const parseTemplate = function (tpl, data) {
    for (let key in data) {
        let value = data[key];
        if (typeof value === 'function') {
            tpl = tpl.replace(new RegExp('\{%' + key + '%\}', 'g'), value(data));
        } else {
            tpl = tpl.replace(new RegExp('\{%' + key + '%\}', 'g'), sanitize(value));
        }
    }

    return tpl;
}
const request = function (url, data, successCallback, errorCallback) {
    if (typeof successCallback !== 'function') {
        successCallback = function () {
        };
    }
    if (typeof errorCallback !== 'function') {
        errorCallback = function () {
        };
    }
    if (typeof data !== 'object') {
        data = {};
    }
    data._token = config.csrf;
    const form_data = new FormData();
    for (let key in data) {
        form_data.append(key, data[key]);
    }
    fetch(new Request(url, {
        method: 'post',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: form_data
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
const destroyWindow = function (wnd) {
    const mask = $('.window-mask');
    wnd.window('destroy', true);
    $('.window-shadow,.window-mask').remove();
    $('body').css('overflow', 'auto').append(mask);
}

