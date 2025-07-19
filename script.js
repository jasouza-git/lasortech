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
    const customers = edit ? await api({
        query: 'customers',
        mode: 'all',
        session_id: getCookie('session')
    }) : { data: [] };
    out.classList.add('card');
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
        <div>
            ${edit ? /*html*/ `<select>
                ${customers.data.map(x => html `<option value="${x.id}">${x.name}</option>`)}
            </select>` : html `<h1>${data.customer.name}</h2>`}
            <p>${edit && customers.data.length ? customers.data[0].description : html `${(_a = data.customer.description) !== null && _a !== void 0 ? _a : ''}`}</p>
            <a class="cn">${edit && customers.data.length ? customers.data[0].contact_number : html `${data.customer.contact_number}`}</a>
            <a class="em">${edit && customers.data.length ? customers.data[0].email : html `${data.customer.email}`}</a>
        </div>
        <div>
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean vitae lectus et justo pretium ullamcorper nec vel mauris. Vestibulum placerat tellus sit amet urna auctor elementum. Maecenas imperdiet feugiat velit, ut sodales dui consectetur vel. Suspendisse in dignissim ipsum, ac rutrum diam. Aenean et suscipit quam. Nulla at odio eu erat finibus pellentesque. Proin porttitor, velit vel bibendum aliquet, augue nulla cursus eros, sit amet consectetur purus metus finibus eros. Mauris volutpat, est sit amet consequat luctus, augue urna efficitur nunc, in placerat lorem ipsum non ligula. Nulla sagittis leo dictum, cursus lorem eu, fermentum felis. Curabitur bibendum libero et blandit viverra. Donec in nisl nec odio pulvinar venenatis in vel ipsum. Maecenas ut ullamcorper lacus. Nulla facilisi. Integer finibus faucibus sapien, in tempor urna ullamcorper id. Curabitur quam turpis, aliquam et luctus eget, vulputate vitae purus. Curabitur vulputate metus ut semper mollis.
        </div>
        <div>
            <div data="${data ? data.state_code : ''}"></div>
            <p onclick="action['order_state'](this)">${data ? data.state.label : 'Not yet created'}</p>
            <h1>${data ? data.rms_code : generateKey()}</h1>
            <button class="noedit" onclick="action['send_order'](this)">
                <icon>&#xf0e0;</icon>
                E-Mail
            </button>
            <button>
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
async function load(quick = false) {
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
    // 2. Request to API
    const datas = await api(Object.assign({ query: sub[0], mode: sub[1], session_id: getCookie('session') }, (q('#find_text')[0].value.length ? { keywords: q('#find_text')[0].value.split(' ') } : {})));
    const d1 = Number(new Date());
    // 3. Load content
    if (!quick && d1 - d0 < 500)
        await new Promise(res => setTimeout(res, 500 - (d1 - d0)));
    q('#new', x => (sub[0] == 'employees' ? x.setAttribute('disabled', '') : x.removeAttribute('disabled')));
    q('#side button', x => {
        const p = x.getAttribute('data').split('/');
        if (p[0] == sub[0] && (sub[1] == 'all' || sub[1] == p[1]))
            x.classList.add('on');
        else
            x.classList.remove('on');
    });
    q('#body', x => x.innerHTML = '');
    if (datas.data != null) {
        if (sub[0] == 'orders') {
            for (const data of datas.data) {
                q('#body')[0].appendChild(await card(data));
            }
        }
        else if (sub[0] == 'customers') {
            row_customer();
            datas.data.map(x => row_customer(x));
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
        <button class="on">1</button>
        <button>2</button>
        <div contenteditable="true">${perpage}</div>
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
    /*---- ORDERS ----- */
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
        Array.from(p.querySelectorAll(':scope>div:first-child>p')).forEach((x) => {
            x.setAttribute('contenteditable', 'true');
        });
    },
    'save_order': async (dom) => {
        var _a;
        const p = dom.parentNode.parentNode;
        const As = Array.from(p.children[0].querySelectorAll('tbody:not(:first-child):not(:last-child)'));
        const id = p.querySelector('select').value;
        const ids = [];
        for (const A of As) {
            console.log(A);
            const res = await api({
                new: 'item',
                session_id: getCookie('session'),
                belonged_customer_id: id,
                name: A.querySelector('td:nth-child(1)').innerText,
                brand: A.querySelector('td:nth-child(2)').innerText,
                model: A.querySelector('td:nth-child(3)').innerText,
                serial: A.querySelector('td:nth-child(4)').innerText,
            });
            if (res.errno)
                throw new Error((_a = res.error) !== null && _a !== void 0 ? _a : 'Unknown');
            ids.push(res.data.id);
        }
        const res = await api({
            new: 'order',
            rms_code: p.children[2].querySelector('h1').innerText,
            description: p.children[0].querySelector('p').innerHTML,
            item_ids: ids,
            session_id: getCookie('session')
        });
        console.log(res);
        //location.reload();
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
    'order_state': async () => {
    }
};
/** New Action */
q('#new', x => x.onclick = async () => {
    console.log(path);
    if (path[0] == 'customers') {
        row_customer(null, true);
    }
    else if (path[0] == 'employee') {
        row_employee();
    }
    else if (path[0] == 'orders') {
        q('#body')[0].appendChild(await card());
    }
});
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
onload = async function () {
    const loggedin = getCookie('session') != null ? (await api({ get: 'current', session_id: getCookie('session') }, () => true)).errno == 0 : false;
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
