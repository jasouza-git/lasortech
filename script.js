// tsc --watch asset/script.ts --outFile script.js --target ES6 --module system --lib DOM,ES6
/** Current path of page */
let path = location.pathname.split('/').slice(1);
/* ----- SHORTCUTS ----- */
/** HTML Code */
function html(strings, ...values) {
    return strings.reduce((result, str, i) => {
        const value = values[i];
        const safe = String(value).replace(/[&<>"'`]/g, function (char) {
            const escapeMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '`': '&#96;',
            };
            return escapeMap[char];
        });
        return result + str + (value !== undefined ? safe : '');
    }, '');
}
/** Generate Query Handler */
function q(code, f = (x, n) => x) {
    return Array.from(document.querySelectorAll(code)).map(f);
}
/** SHA256 Encryption */
const sha256 = async (txt) => Array.from(new Uint8Array(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(txt)))).map(b => b.toString(16).padStart(2, '0')).join('');
/** Sets cookie */
function setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = `${name}=${value}; ${expires}; path=/`;
}
/** Gets cookie */
function getCookie(name) {
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
        const [key, value] = cookie.trim().split('=');
        if (key === name) {
            return decodeURIComponent(value);
        }
    }
    return null;
}
function dateFormat(d = new Date(), f = '%b %d, %Y') {
    if (d == null)
        d = new Date();
    else if (typeof d == 'string')
        d = new Date(d);
    return f
        .replace(/%Y/g, `${d.getFullYear()}`)
        .replace(/%y/g, String(d.getFullYear()).slice(-2))
        .replace(/%m/g, String(d.getMonth() + 1).padStart(2, '0'))
        .replace(/%B/g, d.toLocaleString('en-US', { month: 'long' }))
        .replace(/%b/g, d.toLocaleString('en-US', { month: 'short' }))
        .replace(/%d/g, String(d.getDate()).padStart(2, '0'))
        .replace(/%A/g, d.toLocaleString('en-US', { weekday: 'long' }))
        .replace(/%a/g, d.toLocaleString('en-US', { weekday: 'short' }))
        .replace(/%H/g, String(d.getHours()).padStart(2, '0'))
        .replace(/%I/g, String((d.getHours() % 12) || 12).padStart(2, '0'))
        .replace(/%p/g, d.getHours() < 12 ? 'AM' : 'PM')
        .replace(/%M/g, String(d.getMinutes()).padStart(2, '0'))
        .replace(/%S/g, String(d.getSeconds()).padStart(2, '0'))
        .replace(/%f/g, String(d.getMilliseconds()).padStart(3, '0') + '000') // microseconds
        .replace(/%z/g, d.toTimeString().slice(9))
        .replace(/%Z/g, Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC')
        //.replace(/%j/g, getDayOfYear(d))
        //.replace(/%U/g, getWeekNumber(d)) // approximate
        .replace(/%c/g, d.toLocaleString())
        .replace(/%x/g, d.toLocaleDateString())
        .replace(/%X/g, d.toLocaleTimeString());
}
function generateKey() {
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const groupSize = 5;
    const groupCount = 6;
    let result = [];
    for (let i = 0; i < groupCount; i++) {
        let group = '';
        for (let j = 0; j < groupSize; j++) {
            const randomChar = letters[Math.floor(Math.random() * letters.length)];
            group += randomChar;
        }
        result.push(group);
    }
    return result.join('-');
}
/* ----- COMPONENT GENERATOR ----- */
/** Order Card */
async function card(data = null, edit) {
    var _a;
    const out = document.createElement('div');
    if (edit == undefined)
        edit = data == null;
    const ek = edit ? 'contenteditable="true"' : '';
    const state = data ? data.state_code : 0;
    const state_map = [[1], [2, 4, 5], [], [6], [6], [], [], [1, 2, 3, 4, 5, 6]];
    out.classList.add('card');
    if (!data) {
        out.setAttribute('stage', '1');
        setTimeout(() => out.removeAttribute('stage'), 250);
    }
    if (edit)
        out.classList.add('edit');
    if (data)
        out.setAttribute('data', data.id);
    out.innerHTML = /*html*/ `
        <div>
            <h1>${data ? data.id.slice(0, 24) + '...' : '[ NEW ORDER ]'}</h1>
            <h2>
                <span>${dateFormat(data === null || data === void 0 ? void 0 : data.update_at)}</span> /
                <span>${dateFormat(data === null || data === void 0 ? void 0 : data.create_at)}</span>
            </h2>
            <div>
                <table>
                    <tbody><tr><th>Name</th><th>Brand</th><th>Model</th><th>Serial</th><th class="edit">Options</th></tr></tbody>
                    ${data ? data.items.map(x => html `<tbody><tr data="${x.id}">
                            <td>${x.name}</td>
                            <td>${x.brand}</td>
                            <td>${x.model}</td>
                            <td>${x.serial}</td>
                            <td class="edit"><div>
                                <button onclick="action['order_item_delete'](this)">&#xe2b4;</button>
                            </div></td>
                        </tr></tbody>`).join('') : ''}
                    <tbody><tr class="edit"><div><td colspan="999"><div><button onclick="action['new_item'](this)">+</button></div></div></td></tr></tbody>
                </table>
            </div>
            <p ${ek}>${data ? html `${data.description}` : ''}</p>
        </div>
        <div ${data ? `data="${data.customer.id}"` : ''}>
            <select class="edit" onchange="action['load_order_customer'](this)"></select>
            <h1 class="noedit">${data ? html `${data.customer.name}` : ''}</h1>
            <p>${data ? html `${(_a = data.customer.description) !== null && _a !== void 0 ? _a : ''}` : ''}</p>
            <a class="cn">${data ? html `${data.customer.contact_number}` : ''}</a>
            <a class="em">${data ? html `${data.customer.email}` : ''}</a>
        </div>
        <div>
        </div>
        <div>
            <div class="state" data="${data ? data.state_code : ''}" onclick="action['manage_order'](this)"></div>
            <!--<p onclick="action['order_state'](this)">${data ? data.state.label : 'Not yet created'}</p>-->
            <h1>${data ? data.rms_code : generateKey()}</h1>
            <button class="noedit" onclick="action['send_order'](this)">
                <icon>&#xf0e0;</icon>
                E-Mail
            </button>
            <button onclick="action['delete_order'](this)">
                <icon>&#xf1f8;</icon>
                Delete
            </button>
            <button class="noedit" onclick="action['edit_order'](this)">
                <icon>&#xf304;</icon>
                Edit
            </button>
            <button class="edit" onclick="action['save_order'](this)">
                <icon>&#xf0c7;</icon>
                Save
            </button>
        </div>
    `;
    return out;
}
/** Employee Row */
function row_employee(data = null, edit) {
    var _a, _b, _c, _d;
    const init = q('#body>table').length ? false : true;
    const table = (_a = q('#body>table')[0]) !== null && _a !== void 0 ? _a : document.createElement('table');
    const ek = edit ? 'contenteditable="true"' : '';
    if (edit == undefined)
        edit = data == null;
    if (init)
        table.innerHTML += /*html*/ `<tr>
        <th>Name</th>
        <th>Contact Number</th>
        <th>Messenger</th>
        <th>Description</th>
        <th>Working</th>
        <th>Options</th>
    </tr>`;
    const end = (_b = q('#body>table tbody:has(tr.empty)')[0]) !== null && _b !== void 0 ? _b : document.createElement('tbody');
    if (!q('#body>table tbody:has(tr.empty)').length) {
        end.innerHTML = '<tr class="empty"><td colspan="99999">Empty</th></tr>';
        table.appendChild(end);
    }
    if (data != null) {
        const row = document.createElement('tbody');
        row.innerHTML = /*html*/ `<tr>
            <td ${ek}>${data.name}</td>
            <td ${ek}>${data.contact_number}</td>
            <td ${ek}>${(_c = data === null || data === void 0 ? void 0 : data.messenger_id) !== null && _c !== void 0 ? _c : ''}</td>
            <td ${ek}>${(_d = data === null || data === void 0 ? void 0 : data.description) !== null && _d !== void 0 ? _d : ''}</td>
            <td><input type="checkbox" ${edit ? '' : 'disabled'}${data.working ? ' checked' : ''}/></td>
            <td><div>
                <button>&#xe2b4;</button>
            </div></td>
        </tr>`;
        table.insertBefore(row, end);
    }
    if (init)
        q('#body')[0].appendChild(table);
}
/** Customer Row */
function row_customer(data = null, edit) {
    var _a, _b, _c, _d, _e, _f, _g;
    const init = q('#body>table').length ? false : true;
    const table = (_a = q('#body>table')[0]) !== null && _a !== void 0 ? _a : document.createElement('table');
    const ek = edit ? 'contenteditable="true"' : '';
    if (init)
        table.innerHTML += /*html*/ `<tr>
        <th>Name</th>
        <th>Contact Number</th>
        <th>Email</th>
        <th>Messenger</th>
        <th>Description</th>
        <th>Options</th>
    </tr>`;
    const end = (_b = q('#body>table tbody:has(tr.empty)')[0]) !== null && _b !== void 0 ? _b : document.createElement('tbody');
    if (!q('#body>table tbody:has(tr.empty)').length) {
        end.innerHTML = '<tr class="empty"><td colspan="99999">Empty</th></tr>';
        table.appendChild(end);
    }
    if (data != null || edit) {
        const row = document.createElement('tbody');
        row.innerHTML = /*html*/ `<tr ${(data === null || data === void 0 ? void 0 : data.id) ? `data="${data.id}"` : ''} ${edit ? 'class="edit"' : ''}>
            <td ${ek}>${(_c = data === null || data === void 0 ? void 0 : data.name) !== null && _c !== void 0 ? _c : ''}</td>
            <td ${ek}>${(_d = data === null || data === void 0 ? void 0 : data.contact_number) !== null && _d !== void 0 ? _d : ''}</td>
            <td ${ek}>${(_e = data === null || data === void 0 ? void 0 : data.email) !== null && _e !== void 0 ? _e : ''}</td>
            <td ${ek}>${(_f = data === null || data === void 0 ? void 0 : data.messenger_id) !== null && _f !== void 0 ? _f : ''}</td>
            <td ${ek}>${(_g = data === null || data === void 0 ? void 0 : data.description) !== null && _g !== void 0 ? _g : ''}</td>
            <td><div>
                <button onclick="action['delete_customer'](this)">&#xe2b4;</button>
                <button class="edit" onclick="action['save_customer'](this)">+</button>
                <button class="noedit" onclick="action['edit'](this,'save_customer')">&#xf304;</button>
            </div></td>
        </tr>`;
        table.insertBefore(row, end);
    }
    if (init)
        q('#body')[0].appendChild(table);
}
/** Item Row */
function row_items(data = null, edit) {
    var _a, _b, _c, _d, _e, _f;
    const init = q('#body>table').length ? false : true;
    const table = (_a = q('#body>table')[0]) !== null && _a !== void 0 ? _a : document.createElement('table');
    const ek = edit ? 'contenteditable="true"' : '';
    if (init)
        table.innerHTML += /*html*/ `<tr>
        <th>Name</th>
        <th>Brand</th>
        <th>Model</th>
        <th>Serial</th>
        <th>Owner</th>
        <th>Options</th>
    </tr>`;
    const end = (_b = q('#body>table tbody:has(tr.empty)')[0]) !== null && _b !== void 0 ? _b : document.createElement('tbody');
    if (!q('#body>table tbody:has(tr.empty)').length) {
        end.innerHTML = '<tr class="empty"><td colspan="99999">Empty</th></tr>';
        table.appendChild(end);
    }
    if (data != null || edit) {
        const row = document.createElement('tbody');
        row.innerHTML = /*html*/ `<tr ${(data === null || data === void 0 ? void 0 : data.id) ? `data="${data.id}"` : ''}>
            <td ${ek}>${(_c = data === null || data === void 0 ? void 0 : data.name) !== null && _c !== void 0 ? _c : ''}</td>
            <td ${ek}>${(_d = data === null || data === void 0 ? void 0 : data.brand) !== null && _d !== void 0 ? _d : ''}</td>
            <td ${ek}>${(_e = data === null || data === void 0 ? void 0 : data.model) !== null && _e !== void 0 ? _e : ''}</td>
            <td ${ek}>${(_f = data === null || data === void 0 ? void 0 : data.serial) !== null && _f !== void 0 ? _f : ''}</td>
            <td ${ek}>${(data === null || data === void 0 ? void 0 : data.belonged_customer_id) ? `<a href="/user/${data === null || data === void 0 ? void 0 : data.belonged_customer_id}">User</a>` : 'Unknown'}</td>
            <td><div>
                <button>&#xe2b4;</button>
                ${edit ? `<button onclick="action['save_item'](this)">+</button>` :
            '<button onclick="action[\'edit\'](this,\'save_customer\')">&#xf304;</button>'}
            </div></td>
        </tr>`;
        table.insertBefore(row, end);
    }
    if (init)
        q('#body')[0].appendChild(table);
}
/** Asking popup */
async function pop(tag, title, msg, ops = [], def) {
    /** Dom wrap to allow animating inserts/removes */
    const dom_wrap = document.createElement('div');
    /** Main Dom */
    const dom = document.createElement('div');
    if (tag.length)
        dom.classList.add(tag);
    dom_wrap.appendChild(dom);
    /** Close option? */
    const close = msg.length > 100 || def !== undefined;
    /** Remove popup */
    const remove = () => {
        dom_wrap.setAttribute('step', '1');
        setTimeout(() => q('#popup')[0].removeChild(dom_wrap), 250);
    };
    /** Content */
    dom.innerHTML = /*html*/ `
        <h1>${title}${close ? `<button onclick="action['popout'](this${def === undefined ? '' : html `, '${def}'`})"><icon>&#xf00d;</icon></button>` : ''}</h1>
        <p></p>
        ${ops.map(x => /*html*/ `<button>${x}</button>`)}
    `;
    dom.querySelector('p').innerHTML = msg;
    /** Text Input */
    const inp = document.createElement('textarea');
    if (def != undefined)
        dom.querySelector('p').appendChild(inp);
    /** Setup button triggers */
    let trig = x => { };
    Array.from(dom.querySelectorAll(':scope>button')).forEach((x, n) => {
        x.addEventListener('click', y => trig(n));
    });
    /** Add to popups with animation (step=0) */
    q('#popup')[0].appendChild(dom_wrap);
    setTimeout(() => dom_wrap.setAttribute('step', '0'), 1);
    if (def != undefined)
        setTimeout(() => inp.focus(), 1);
    /** Remove automatically */
    if (!close && !ops.length)
        setTimeout(remove, 5000);
    /** Throws error if error */
    if (tag == 'error' && !ops.length)
        return new Error(title + '\n' + msg);
    /** Capture user input */
    const num = await new Promise(res => {
        trig = res;
    });
    /** Close popup */
    remove();
    return [ops[num], inp.value];
}
/** API Request */
async function api(data, cont = () => { }) {
    var _a, _b;
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(data)) {
        if (Array.isArray(value)) {
            for (const subval of value) {
                params.append(`${key}[]`, String(subval));
            }
        }
        else
            params.append(key, String(value));
    }
    const res = await fetch('/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    }).then(res => res.json());
    const out = cont(res);
    console.log(out);
    if (out !== true && res.errno) {
        let msg = (_a = res.errorname) !== null && _a !== void 0 ? _a : `Error #${res.errno}`;
        let dat = (_b = res.error) !== null && _b !== void 0 ? _b : 'Unknown';
        throw pop('error', msg, dat);
    }
    return res;
}
/** Loads page */
async function load(quick = false, page = 0) {
    var _a;
    // 1. Prepare request
    const d0 = Number(new Date());
    let perpage = Number((_a = q('#tabs div[contenteditable="true"]', x => x.innerText)[0]) !== null && _a !== void 0 ? _a : '10');
    if (Number.isNaN(perpage))
        perpage = 10;
    if (!quick)
        document.body.classList.add('loading');
    const sub = [path.length > 0 && path[0].length ? path[0] : 'orders', path.length > 1 && path[1].length ? path[1] : 'all'];
    path = sub;
    // 1.1 Maybe logout?
    if (sub[0] == 'logout') {
        document.cookie = "session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        location.reload();
        return;
    }
    // 2. Get number of datas for proper page identification
    const count = (await api(Object.assign(Object.assign({ query: sub[0], mode: sub[1], session_id: getCookie('session') }, (q('#find_text')[0].value.length ? { keywords: q('#find_text')[0].value.split(' ') } : {})), { get_count_only: true }))).data.count;
    // 3. Request to API
    const datas = await api(Object.assign({ query: sub[0], mode: sub[1], session_id: getCookie('session'), page, count_per_page: perpage }, (q('#find_text')[0].value.length ? { keywords: q('#find_text')[0].value.split(' ') } : {})));
    const d1 = Number(new Date());
    // 4. Load content
    if (!quick && d1 - d0 < 500)
        await new Promise(res => setTimeout(res, 500 - (d1 - d0)));
    q('#side button', x => {
        const p = x.getAttribute('data').split('/');
        if (p[0] == sub[0] && (sub[1] == 'all' || sub[1] == p[1]))
            x.classList.add('on');
        else
            x.classList.remove('on');
    });
    const newbutton = document.createElement('button');
    newbutton.setAttribute('onclick', 'action["new"](this)');
    q('#body', x => x.innerHTML = '');
    if (datas.data != null) {
        if (sub[0] == 'orders') {
            for (const data of datas.data)
                q('#body')[0].appendChild(await card(data));
            q('#body')[0].appendChild(newbutton);
        }
        else if (sub[0] == 'customers') {
            row_customer();
            datas.data.map(x => row_customer(x));
            q('#body')[0].appendChild(newbutton);
        }
        else if (sub[0] == 'items') {
            row_items();
            datas.data.map(x => row_items(x));
        }
        else {
            for (const data of datas.data)
                row_employee(data);
        }
    }
    else {
        q('#body', x => {
            var _a, _b;
            return x.innerHTML = /*html*/ `
            <div class="error">
                <h1>${(_a = datas.error) !== null && _a !== void 0 ? _a : 'Unknown Error'}${datas.reason ? ': ' : ''}${(_b = datas.reason) !== null && _b !== void 0 ? _b : ''}</h1>
                <p>Error code ${datas.errno}</p>
            </div>
        `;
        });
    }
    q('#tabs', x => x.innerHTML = /*html*/ `
        ${Array(Math.ceil(count / perpage)).fill(0).map((_, x) => /*html*/ `
            <button ${x == page ? 'class="on"' : `onclick="load(false,${x})"`}>${x + 1}</button>
        `)}
        <div contenteditable="true" onkeyup="load(true)">${perpage}</div>
    `);
    // 4. Remove Animation
    if (!quick) {
        await new Promise(res => setTimeout(res, (d1 - d0) % 500));
    }
    document.body.classList.remove('load');
    document.body.classList.remove('loading');
}
/** Actions */
const action = {
    'password_check': (pass) => {
        const req = [];
        console.log(pass);
        // Minimum eight characters, at least one letter and one number
        if (pass.length < 8)
            req.push('Minimum eight character');
        if (!/[0-9]/g.test(pass) || pass.match(/[0-9]/g).length < 1)
            req.push('At least one number');
        if (!/[a-zA-Z]/g.test(pass) || pass.match(/[a-zA-Z]/g).length < 1)
            req.push('At least one letter');
        if (req.length)
            throw pop('error', 'Invalid Password', `Please fulfill the following requirements:<ul>${req.map(x => `<li>${x}</li>`)}</ul>`);
    },
    'signup': async () => {
        const [name, contact_number, email, pass, pass2, code] = q('#login .signup input[name]', x => x.value);
        if (pass != pass2)
            return q('#login .signup>p', x => pop('error', 'Failed', 'Passwords do not match!'));
        if (!pass.length)
            throw pop('error', 'Missing field', 'Please enter password');
        if (!name.length)
            throw pop('error', 'Missing field', 'Please enter username');
        if (!contact_number.length)
            throw pop('error', 'Missing field', 'Please enter contact number');
        action['password_check'](pass);
        await api({
            action: 'register',
            password_hashed: await sha256(pass),
            name,
            contact_number,
            email,
            email_verification_code: code
        });
        q('#login>div:last-child>button:nth-child(3)', x => x.click());
    },
    'signup_verify': async (dom) => {
        const email = q('#login .signup input[name=email]')[0].value;
        if (!email.length)
            throw pop('error', 'Missing Data', 'Please enter email!');
        dom.innerHTML = 'Sending...';
        await api({
            action: 'send_verification_email',
            email,
            check_exist: false
        }, res => dom.innerHTML = res.errno ? 'Try Again' : 'Get Verification Code');
        q('#signup_verify', x => x.style.display = 'flex');
        q('#signup_verify input')[0].focus();
    },
    'login': async (dom) => {
        const email = q('#login .login>[name=email]')[0].value, pass = q('#login .login>[name=pass]')[0].value;
        if (!email.length)
            throw pop('error', 'Missing data', 'Please enter email!');
        if (!pass.length)
            throw pop('error', 'Missing data', 'Please enter password!');
        dom.innerHTML = 'Logging in...';
        const res = await api({
            action: 'login',
            email,
            password_hashed: await sha256(pass)
        }, (res) => dom.innerHTML = res.errno ? 'Retry Login' : 'Login');
        if (res.data != null) {
            document.body.classList.remove('login');
            setCookie('session', res.data.id, 7);
            load();
        }
    },
    'edit': async (dom, com) => {
        const p = dom.parentNode.parentNode.parentNode;
        Array.from(p.querySelectorAll(':scope>td:not(:has(>div)):not(:has(>input))')).map((x) => x.setAttribute('contenteditable', ''));
        dom.innerHTML = '+';
        dom.onclick = () => action[com](dom);
    },
    'new_item': async (dom) => {
        const p = dom.parentNode.parentNode.parentNode.parentNode;
        const T = document.createElement('tbody');
        const ek = 'contenteditable="true"';
        T.innerHTML = /*html*/ `<tr>
            <td ${ek}></td>
            <td ${ek}></td>
            <td ${ek}></td>
            <td ${ek}></td>
            <td class="edit"><div>
                <button onclick="action['order_item_delete'](this)">&#xe2b4;</button>
            </div></td>
        </tr>`;
        p.parentNode.insertBefore(T, p);
    },
    'send_code': async (dom) => {
        const email = q('#login_forgot_email')[0].value;
        if (!email.length)
            throw pop('error', 'Missing Data', 'Please enter email!');
        dom.innerHTML = 'Sending...';
        await api({
            action: 'send_verification_email',
            email,
            check_exist: true
        }, () => {
            dom.innerHTML = 'Resend Code';
        });
        q('#login_forgot_verify', x => x.style.display = 'flex');
        q('#login_forgot_verify input')[0].focus();
    },
    'change_pass': async (dom) => {
        const [email, code, pass] = q('#login_forgot_email,#login_forgot_code,#login_forgot_pass', x => x.value);
        if (!email.length)
            throw pop('error', 'Missing Data', 'Please enter email!');
        if (!code.length)
            throw pop('error', 'Missing Data', 'Please enter verification code!');
        if (!pass.length)
            throw pop('error', 'Missing Data', 'Please enter new password!');
        action['password_check'](pass);
        dom.innerHTML = 'Changing...';
        await api({
            action: 'forgot_password',
            email,
            password_hashed: await sha256(pass),
            email_verification_code: code,
        }, (res) => {
            if (res.errno)
                dom.innerHTML = 'Retry';
        });
        q('#login_forgot_verify', x => x.style.display = 'none');
        q('#login>div:last-child>button:nth-child(3)', x => x.click());
        q('#login_forgot_sendcode', x => x.innerHTML = 'Send Verification Code');
        dom.innerHTML = 'Change Password';
    },
    'popout': async (dom, def) => {
        const p = dom.parentNode.parentNode.parentNode;
        if (def != undefined)
            Array.from(p.querySelectorAll('button'), (x) => {
                if (x.innerText == def)
                    x.click();
            });
        else {
            p.setAttribute('step', 1);
            setTimeout(() => p.parentNode.removeChild(p), 250);
        }
    },
    'new': async (dom) => {
        console.log(path);
        if (path[0] == 'customers') {
            row_customer(null, true);
        }
        else if (path[0] == 'employee') {
            row_employee();
        }
        else if (path[0] == 'orders') {
            const c = await card();
            q('#body')[0].insertBefore(c, dom);
            await action['load_order_customer'](c);
        }
    },
    /* ----- CUSTOMERS ----- */
    'delete_customer': async (dom) => {
        const p = dom.parentNode.parentNode.parentNode;
        if (p.hasAttribute('data')) {
            const [confirm] = await pop('warning', 'Delete?', 'Delete customer?', ['no', 'yes']);
            if (confirm == 'no')
                return;
            await api({
                delete: 'customers',
                ids: [p.getAttribute('data')],
                session_id: getCookie('session'),
            });
        }
        p.parentElement.removeChild(p);
    },
    'save_customer': async (dom) => {
        var _a;
        const p = dom.parentNode.parentNode.parentNode;
        const id = (_a = p.getAttribute('data')) !== null && _a !== void 0 ? _a : '', name = p.children[0].innerText, contact = p.children[1].innerText, email = p.children[2].innerText, messenger = p.children[3].innerText, descript = p.children[4].innerText;
        if (!name.length)
            throw pop('error', 'Missing field', 'Enter customer name!');
        if (!contact.length)
            throw pop('error', 'Missing field', 'Enter customer contact!');
        if (!email.length)
            throw pop('error', 'Missing field', 'Enter customer email!');
        Array.from(p.querySelectorAll(':scope>td:not(:has(>div)):not(:has(>input))')).map((x) => x.removeAttribute('contenteditable'));
        dom.innerHTML = '&#xe1d4;';
        const rest = api(Object.assign(Object.assign(Object.assign(Object.assign(Object.assign({}, (id.length ? { update: 'customer', id } : { new: 'customer' })), { name, contact_number: contact, email }), (messenger.length ? { messenger_id: messenger } : {})), (descript.length ? { description: descript } : {})), { session_id: getCookie('session') }));
        dom.innerHTML = '&#xf304;';
        dom.onclick = () => action['edit'](dom, 'save_customer');
    },
    /* ----- ORDERS ----- */
    'delete_order': async (dom) => {
        const p = dom.parentNode.parentNode;
        if (p.hasAttribute('data')) {
            const confirm = await pop('warning', 'Delete Order?', 'Are you sure you want to delete order?', ['no', 'yes']);
            if (confirm[0] == 'no')
                return;
            await api({
                delete: 'orders',
                ids: [p.getAttribute('data')],
                session_id: getCookie('session')
            });
        }
        p.setAttribute('stage', '1');
        setTimeout(() => {
            p.setAttribute('stage', '2');
            setTimeout(() => p.parentNode.removeChild(p), 250);
        }, 250);
    },
    'send_order': async (dom) => {
        const p = dom.parentNode.parentNode;
        const msg = await pop('', 'Send E-Mail', 'Enter message to customer', ['cancel', 'send'], 'cancel');
        if (msg[0] == 'cancel')
            return;
        await api({
            email: 'order',
            id: p.getAttribute('data'),
            message: msg[1],
            session_id: getCookie('session')
        });
    },
    'edit_order': async (dom) => {
        const p = dom.parentNode.parentNode;
        p.classList.add('edit');
        p.classList.remove('order');
        Array.from(p.querySelectorAll(':scope>div:first-child>p,:scope>div:first-child tr:not(.edit) td')).forEach((x) => {
            x.setAttribute('contenteditable', 'true');
        });
        await action['load_order_customer'](p.querySelector('select'));
    },
    'save_order': async (dom) => {
        const p = dom.parentNode.parentNode;
        const As = Array.from(p.children[0].querySelectorAll('tbody:not(:first-child):not(:last-child)'));
        const id = p.querySelector(':scope>div:nth-child(2)').getAttribute('data');
        const ids = [];
        for (const A of As) {
            const res = A.querySelector('tr[data]') ? await api({
                id: A.querySelector('tr[data]').getAttribute('data'),
                update: 'item',
                session_id: getCookie('session'),
                belonged_customer_id: id,
                name: A.querySelector('td:nth-child(1)').innerText,
                brand: A.querySelector('td:nth-child(2)').innerText,
                model: A.querySelector('td:nth-child(3)').innerText,
                serial: A.querySelector('td:nth-child(4)').innerText,
            }) : await api({
                new: 'item',
                session_id: getCookie('session'),
                belonged_customer_id: id,
                name: A.querySelector('td:nth-child(1)').innerText,
                brand: A.querySelector('td:nth-child(2)').innerText,
                model: A.querySelector('td:nth-child(3)').innerText,
                serial: A.querySelector('td:nth-child(4)').innerText,
            });
            if (!A.querySelector('tr[data]'))
                ids.push(res.data.id);
        }
        const res = p.hasAttribute('data') ? await api({
            update: 'order',
            id: p.getAttribute('data'),
            session_id: getCookie('session'),
            description: p.querySelector(':scope>div:first-child p').innerText
        }) : await api({
            new: 'order',
            rms_code: p.children[3].querySelector('h1').innerText,
            description: p.children[0].querySelector('p').innerHTML,
            item_ids: ids,
            session_id: getCookie('session'),
            customer_id: id
        });
        // Set up uneditable order
        const customer_name = document.createElement('h1');
        customer_name.innerText = p.querySelector(`select option[value="${id}"]`).innerText;
        p.children[0].querySelector('h1').innerText = res.data.id;
        p.children[1].insertBefore(customer_name, p.querySelector('select'));
        p.children[1].removeChild(p.querySelector('select'));
        p.classList.remove('edit');
        p.setAttribute('data', res.data.id);
        Array.from(p.querySelectorAll('[contenteditable]')).map((x) => x.removeAttribute('contenteditable'));
        console.log('DATA', res.data);
        p.querySelector(':scope>div:nth-child(4)>div.state').setAttribute('state', res.data.state_code);
        //location.reload();
    },
    'load_order_customer': async (dom) => {
        var _a, _b, _c, _d;
        const s = dom.tagName == 'SELECT' ? dom : dom.querySelector('select');
        const p = s.parentElement;
        let customer = null;
        if (!s.children.length) {
            const customers = await api({
                query: 'customers',
                mode: 'all',
                session_id: getCookie('session')
            });
            for (const cus of customers.data) {
                s.innerHTML += html `<option value="${cus.id}">${cus.name}</option>`;
            }
            customer = customers.data[0];
        }
        if (customer == null) {
            const uid = s.value;
            customer = (await api({
                fetch: 'customers',
                ids: [uid],
                session_id: getCookie('session')
            })).data[0];
        }
        p.querySelector('h1').innerText = (_a = customer.name) !== null && _a !== void 0 ? _a : '';
        p.querySelector('p').innerText = (_b = customer.description) !== null && _b !== void 0 ? _b : '';
        p.querySelector('a.cn').innerText = (_c = customer.contact_number) !== null && _c !== void 0 ? _c : '';
        p.querySelector('a.em').innerText = (_d = customer.email) !== null && _d !== void 0 ? _d : '';
        p.setAttribute('data', customer.id);
        console.log(customer);
    },
    'load_order_states': async (body, id) => {
        var _a, _b, _c;
        const state_map = [[1], [2, 4, 5, 7], [1, 3, 7], [], [6], [6], [], [1, 2, 3, 4, 5, 6]];
        Array.from(body.children).map((x) => x.parentNode.removeChild(x));
        const states = await api({
            query: 'states',
            session_id: getCookie('session'),
            order_ids: [id]
        });
        for (const state of states.data) {
            const dom = document.createElement('div');
            dom.innerHTML = /*html*/ `
                <div class="state" data="${state.state_code}"></div>
                <span class="date">${dateFormat(state.create_at)}</span>
                ${state.employee_id ? html `<a href="/employee?id=${state.employee_id}" class="employee">Employee</a>` : ''}
                ${state.reason || typeof state.amount == 'number' ? html `<p>${(_a = state.reason) !== null && _a !== void 0 ? _a : `Customer paid ${state.amount.toLocaleString('en-PH', {
                style: 'currency',
                currency: 'PHP',
                minimumFractionDigits: 2
            })}`}</p>` : ''}
            `;
            body.appendChild(dom);
        }
        const state = (_c = (_b = states.data[states.data.length - 1]) === null || _b === void 0 ? void 0 : _b.state_code) !== null && _c !== void 0 ? _c : 0;
        const dom = document.createElement('div');
        dom.innerHTML = /*html*/ `
            ${state_map[state].map(n => /*html*/ `
                <div>
                    <div class="state" data="${n}" onclick="action['state_add'](this,${n})"></div>
                </div>
            `).join('')}
        `;
        body.appendChild(dom);
        body.style.maxHeight = `${body.scrollHeight}px`;
        body.parentElement.querySelector(':scope>div:nth-child(4)>div.state').setAttribute('data', state);
    },
    'manage_order': async (dom) => {
        const p = dom.parentElement.parentElement;
        const body = p.querySelector(':scope>div:nth-child(3)');
        p.classList.toggle('order');
        if (!p.classList.contains('order')) {
            body.style.maxHeight = '0';
            return;
        }
        action['load_order_states'](body, p.getAttribute('data'));
    },
    /* ----- STATES ----- */
    'state_add': async (dom, n) => {
        const p = dom.parentElement.parentElement.parentElement.parentElement;
        //const cstate = Number(p.querySelector(':scope>div:nth-child(4)>div.state[data]').getAttribute('data'));
        //if (Number.isNaN(cstate)) throw pop('error', 'System failure', 'Failed to get current state');
        let args = {};
        if (n == 1) {
            const prompt = await pop('', 'Processing report', 'Enter reason of processing', ['cancel', 'add'], 'cancel');
            if (prompt[0] == 'cancel')
                return;
            args = Object.assign(Object.assign({}, args), { employee_id, reason: prompt[1] });
        }
        else if (n == 4) {
            const prompt = await pop('', 'Incomplete report', 'Enter reason of incompletion', ['cancel', 'add'], 'cancel');
            if (prompt[0] == 'cancel')
                return;
            args = Object.assign(Object.assign({}, args), { reason: prompt[1] });
        }
        else if (n == 5) {
            const prompt = await pop('', 'Cancel report', 'Enter reason of cancellation', ['cancel', 'add'], 'cancel');
            if (prompt[0] == 'cancel')
                return;
            args = Object.assign(Object.assign({}, args), { reason: prompt[1] });
        }
        else if (n == 7) {
            const prompt = await pop('', 'Payment report', 'Enter amount of payment', ['cancel', 'add'], 'cancel');
            if (prompt[0] == 'cancel')
                return;
            args = Object.assign(Object.assign({}, args), { amount: prompt[1] });
        }
        await api(Object.assign({ new: 'state', session_id: getCookie('session'), order_id: p.getAttribute('data'), state_code: n }, args));
        action['load_order_states'](p.querySelector(':scope>div:nth-child(3)'), p.getAttribute('data'));
    }
};
/** Find */
q('#find_text')[0].onkeyup = q('#search')[0].click = () => load(true);
/** Find filters */
q('#side>button', x => x.addEventListener('click', () => {
    let p = x.getAttribute('data').split('/');
    x.classList.toggle('on');
    const cs = [];
    q('#side>button', y => {
        if (x == y)
            return;
        console.log(y);
        const p2 = y.getAttribute('data').split('/');
        let c = p[0] != p2[0] ? false : p[1] == 'all' ? x.classList.contains('on') : p2[1] == 'all' ? y.nextSibling.classList.contains('on') && y.nextSibling.nextSibling.classList.contains('on') : y.classList.contains('on');
        if (y.classList.contains('on') != c)
            y.classList.toggle('on');
        if (c)
            cs.push(y);
    });
    if (!x.classList.contains('on'))
        p = cs.length ? cs[0].getAttribute('data').split('/') : null;
    if (p != null) {
        path = p;
        load();
    }
    console.log(path);
}));
/** Automatic next element on enter */
q('#login input', x => x.addEventListener('keyup', e => {
    if (e.keyCode == 13) {
        let dom = x.nextSibling;
        let n = 0;
        while (dom && n != 10) {
            let bk = true;
            if (dom.tagName == 'INPUT')
                dom.focus();
            else if (dom.tagName == 'BUTTON')
                dom.click();
            else
                bk = false;
            if (bk)
                break;
            dom = dom.nextSibling;
            n++;
        }
        if (n == 10)
            throw new Error('Failed to find next element');
    }
}));
/** Adding event to login buttons */
q('#login>div:last-child>button', (x, n) => x.addEventListener('click', () => {
    q('#login>div.on,#login>div:last-child>button.on', y => y.classList.remove('on'));
    x.classList.add('on');
    q('#login>div')[n].classList.add('on');
}));
/** Onloaded */
let employee_id = '';
onload = async function () {
    var _a, _b;
    let loggedin = false;
    if (getCookie('session') != null) {
        const res = await api({ get: 'current', session_id: getCookie('session') }, () => true);
        if (res.errno == 0) {
            loggedin = true;
            q('#user_name', x => x.innerText = res.data.name);
        }
        employee_id = (_b = (_a = res.data) === null || _a === void 0 ? void 0 : _a.id) !== null && _b !== void 0 ? _b : '';
    }
    if (loggedin) {
        await load();
        q('.t20p_title', x => x.beginElement());
    }
    else {
        await new Promise(res => setTimeout(res, 500));
        q('.t20p_title', x => x.beginElement());
        document.body.classList.add('login');
        q('#login .login input[name=email]', x => x.focus());
    }
};
