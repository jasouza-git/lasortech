// tsc --watch asset/script.ts --outFile script.js --target ES6 --module system --lib DOM,ES6
let path = location.pathname.split('/').slice(1);
/* ----- SHORTCUTS ----- */
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
function card(data = null, edit) {
    const out = document.createElement('div');
    if (edit == undefined)
        edit = data != null;
    const ek = edit ? 'contenteditable="true"' : '';
    out.classList.add('card');
    out.classList.add('edit');
    out.innerHTML = /*html*/ `
        <div>
            <h1>${data ? data.order_id : '[ NEW ORDER ]'}</h1>
            <h2>
                <span>${dateFormat(data === null || data === void 0 ? void 0 : data.update)}</span> /
                <span>${dateFormat(data === null || data === void 0 ? void 0 : data.create)}</span>
            </h2>
            <div>
                <table>
                    <tbody><tr><th>Name</th><th>Brand</th><th>Model</th><th>Serial</th><th class="edit">Options</th></tr></tbody>
                    <tbody><tr class="edit"><div><td colspan="999"><div><button onclick="action['new_item'](this)">+</button></div></div></td></tr></tbody>
                </table>
            </div>
            <p ${ek}>${data ? data.description : ''}</p>
        </div>
        <div>
            <h1>Customer Name</h2>
            <p>Customer Description</p>
            <a class="fb">jkergre</a>
            <a class="cn">090230944545</a>
            <a class="em">erger@hnrh.vof</a>
        </div>
        <div>
            <div data="0"></div>
            <p>${data ? data.status : 'Not yet created'}</p>
            <h1>${data ? data.rms : generateKey()}</h1>
        </div>
        <div>
            <button>
                <icon>&#xf1f8;</icon>
                Delete
            </button>
            <button>
                <icon>&#xf0c7;</icon>
                Save
            </button>
        </div>
    `;
    return out;
}
function row_employee(data = null, edit) {
    var _a, _b;
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
    const end = (_b = q('#body>table tr.empty')[0]) !== null && _b !== void 0 ? _b : document.createElement('tbody');
    if (!q('#body>table tr.empty').length) {
        end.classList.add('empty');
        end.innerHTML = '<tr><td colspan="99999">Empty</th></tr>';
        table.appendChild(end);
    }
    if (data != null) {
        const row = document.createElement('tbody');
        row.innerHTML = /*html*/ `<tr>
            <td ${ek}>${data.name}</td>
            <td ${ek}>${data.contact_number}</td>
            <td ${ek}>${data.messenger_id}</td>
            <td ${ek}>${data.description}</td>
            <td><input type="checkbox" ${edit ? '' : 'disabled'}${data.working ? ' checked' : ''}/></td>
            <td><div>
                <button>&#xe2b4;</button>
                <button>&#xf304;</button>
            </div></td>
        </tr>`;
        table.insertBefore(row, end);
    }
    if (init)
        q('#body')[0].appendChild(table);
}
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
        row.innerHTML = /*html*/ `<tr ${(data === null || data === void 0 ? void 0 : data.id) ? `data="${data.id}"` : ''}>
            <td ${ek}>${(_c = data === null || data === void 0 ? void 0 : data.name) !== null && _c !== void 0 ? _c : ''}</td>
            <td ${ek}>${(_d = data === null || data === void 0 ? void 0 : data.contact_number) !== null && _d !== void 0 ? _d : ''}</td>
            <td ${ek}>${(_e = data === null || data === void 0 ? void 0 : data.email) !== null && _e !== void 0 ? _e : ''}</td>
            <td ${ek}>${(_f = data === null || data === void 0 ? void 0 : data.messenger_id) !== null && _f !== void 0 ? _f : ''}</td>
            <td ${ek}>${(_g = data === null || data === void 0 ? void 0 : data.description) !== null && _g !== void 0 ? _g : ''}</td>
            <td><div>
                <button>&#xe2b4;</button>
                ${edit ? `<button onclick="action['save_customer'](this)">+</button>` :
            '<button onclick="action[\'edit\'](this,\'save_customer\')">&#xf304;</button>'}
            </div></td>
        </tr>`;
        table.insertBefore(row, end);
    }
    if (init)
        q('#body')[0].appendChild(table);
}
async function api(data) {
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
    return res;
}
/** Loads page */
async function load(quick = false) {
    // 1. Prepare request
    const d0 = Number(new Date());
    if (!quick)
        document.body.classList.add('loading');
    const sub = [path.length > 0 && path[0].length ? path[0] : 'order', path.length > 1 && path[1].length ? path[1] : 'all'];
    // 2. Request to API
    const datas = await api(Object.assign({ query: sub[0], mode: sub[1], session_id: getCookie('session') }, (q('#find_text')[0].value.length ? { keywords: q('#find_text')[0].value.split(' ') } : {})));
    const d1 = Number(new Date());
    // 3. Load content
    if (!quick && d1 - d0 < 500)
        await new Promise(res => setTimeout(res, 500 - (d1 - d0)));
    q('#new', x => (sub[0] == 'employees' ? x.setAttribute('disabled', '') : x.removeAttribute('disabled')));
    q('#find input[type=checkbox]', x => {
        const p = x.id.split('_').slice(1);
        if (p[0] == sub[0] && (sub[1] == 'all' || sub[1] == p[1]))
            x.setAttribute('checked', '');
        else
            x.removeAttribute('checked');
    });
    q('#body', x => x.innerHTML = '');
    if (datas.data != null) {
        if (sub[0] == 'orders') {
        }
        else if (sub[0] == 'customers') {
            row_customer();
            datas.data.map(x => row_customer(x));
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
    // 4. Remove Animation
    if (!quick) {
        await new Promise(res => setTimeout(res, (d1 - d0) % 500));
    }
    document.body.classList.remove('load');
    document.body.classList.remove('loading');
}
async function dom_save(dom) {
    const par = dom.parentNode.parentNode.parentNode;
    const data = {
        name: par.children[0].innerText,
        contact_number: par.children[1].innerText,
        email: par.children[2].innerText,
        messenger_id: par.children[3].innerText,
        description: par.children[4].innerText,
        working: par.children[5].children[0].checked
    };
    if (!data.messenger_id.length)
        delete data.messenger_id;
    if (!data.description.length)
        delete data.description;
    console.log(data);
    const res = await api(Object.assign({ new: 'employee' }, data));
    if (!res.errno) {
        par.classList.remove('edit');
        par.children.map(x => x.removeAttribute('contenteditable'));
    }
}
/** Actions */
const action = {
    'signup': async () => {
        const pass = q('#login .signup>[name=pass],#login .signup>[name=pass2]', x => x.value);
        if (pass[0] != pass[1])
            return q('#login .signup>p', x => x.innerText = 'Passwords do not match!');
        const name = q('#login .signup>[name=name]')[0].value, contact_number = q('#login .signup>[name=contact]')[0].value, email = q('#login .signup>[name=email]')[0].value;
        if (!pass[0].length || !name.length || !contact_number.length)
            return q('#login .signup>p', x => x.innerText = 'Please dont leave blanks!');
        const res = await api({
            action: 'register',
            password_hashed: await sha256(pass[0]),
            name,
            contact_number,
            email
        });
        if (!res.errno) {
            q('#login>div:last-child>button:nth-child(3)', x => x.click());
        }
        else
            q('#login .signup>p', x => { var _a; return x.innerText = 'Error: ' + ((_a = res.error) !== null && _a !== void 0 ? _a : 'Unknown'); });
    },
    'login': async () => {
        const email = q('#login .login>[name=email]')[0].value, pass = q('#login .login>[name=pass]')[0].value;
        if (!pass.length || !email.length)
            return q('#login .login>p', x => x.innerText = 'Please dont leave blanks!');
        const res = await api({
            action: 'login',
            email,
            password_hashed: await sha256(pass)
        });
        if (!res.errno && res.data != null) {
            document.body.classList.remove('login');
            setCookie('session', res.data.id, 7);
            load();
        }
        else
            q('#login .login>p', x => { var _a; return x.innerText = 'Error: ' + ((_a = res.error) !== null && _a !== void 0 ? _a : 'Unknown'); });
    },
    'save_customer': async (dom) => {
        var _a;
        const p = dom.parentNode.parentNode.parentNode;
        const id = (_a = p.getAttribute('data')) !== null && _a !== void 0 ? _a : '', name = p.children[0].innerText, contact = p.children[1].innerText, email = p.children[2].innerText, messenger = p.children[3].innerText, descript = p.children[4].innerText;
        if ([name, contact, email, messenger, descript].some(x => !x.length))
            return;
        Array.from(p.querySelectorAll(':scope>td:not(:has(>div)):not(:has(>input))')).map((x) => x.removeAttribute('contenteditable'));
        dom.innerHTML = '&#xe1d4;';
        const rest = api(Object.assign(Object.assign({}, (id.length ? { update: 'customer', id } : { new: 'customer' })), { name, contact_number: contact, email, messenger_id: messenger, description: descript, session_id: getCookie('session') }));
        dom.innerHTML = '&#xf304;';
        dom.onclick = () => action['edit'](dom, 'save_customer');
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
                <button>&#xe2b4;</button>
            </div></td>
        </tr>`;
        p.parentNode.insertBefore(T, p);
    }
};
/** New Action */
q('#new', x => x.onclick = () => {
    if (path[0] == 'customers') {
        row_customer(null, true);
    }
    else if (path[0] == 'employee') {
        row_employee();
    }
    else if (path[0] == 'orders') {
        q('#body')[0].appendChild(card());
    }
});
/** Find */
q('#find_text')[0].onkeyup = q('#search')[0].click = () => load(true);
/** Find filters */
let change = false;
q('#find input[type=checkbox]', x => x.addEventListener('change', () => {
    if (change)
        return;
    change = true;
    let p = x.id.split('_').splice(1);
    const cs = [];
    q('#find input[type=checkbox]', y => {
        if (x == y)
            return;
        const p2 = y.id.split('_').slice(1);
        let c = p[0] != p2[0] ? false : p[1] == 'all' ? x.checked : p2[1] == 'all' ? y.parentElement.nextSibling.children[0].checked && y.parentElement.nextSibling.nextSibling.children[0].checked : y.checked;
        if (y.checked != c)
            y.click();
        if (c)
            cs.push(y);
    });
    if (!x.checked)
        p = cs.length ? cs[0].id.split('_').slice(1) : null;
    if (p != null) {
        path = p;
        load();
    }
    change = false;
    console.log(p);
}));
/** Adding event to login buttons */
q('#login>div:last-child>button', (x, n) => x.addEventListener('click', () => {
    q('#login>div.on,#login>div:last-child>button.on', y => y.classList.remove('on'));
    x.classList.add('on');
    q('#login>div')[n].classList.add('on');
}));
/** Onloaded */
onload = async function () {
    const user = await api({ get: 'current', session_id: getCookie('session') });
    await new Promise(res => setTimeout(res, 500));
    q('.t20p_title', x => x.beginElement());
    if (user.errno) {
        document.body.classList.add('login');
    }
    else {
        await load();
    }
};
