var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
// tsc --watch asset/script.ts --outFile script.js --target ES6 --module system --lib DOM,ES6
let path = location.pathname.split('/').slice(1);
/** Generate Query Handler */
function q(code, f = x => x) {
    return Array.from(document.querySelectorAll(code)).map(f);
}
/** Triggers main logo animation */
function animate(name) {
    q(`.t20p_${name}`, x => x.beginElement());
}
function read(query, mode) {
    return __awaiter(this, void 0, void 0, function* () {
        const par = new URLSearchParams({
            query,
            mode
        });
        const res = yield fetch(`/api.php?${par.toString()}`).then(res => res.json());
        return res;
    });
}
function api(data) {
    return __awaiter(this, void 0, void 0, function* () {
        const params = new URLSearchParams();
        for (const [key, value] of Object.entries(data)) {
            params.append(key, String(value));
        }
        const res = yield fetch('/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        }).then(res => res.json());
        return res;
    });
}
/** Loads page */
function load() {
    return __awaiter(this, void 0, void 0, function* () {
        // 1. Prepare request
        const d0 = Number(new Date());
        document.body.classList.add('loading');
        const sub = [path.length > 0 && path[0].length ? path[0] : 'order', path.length > 1 && path[1].length ? path[1] : 'all'];
        // 2. Request to API
        const datas = yield read(sub[0], sub[1]);
        const d1 = Number(new Date());
        // 3. Load content
        if (d1 - d0 < 500)
            yield new Promise(res => setTimeout(res, 500 - (d1 - d0)));
        q('#body', x => x.innerHTML = '');
        if (datas.data != null) {
            if (sub[0] == 'orders') {
            }
            else {
                const T = document.createElement('table');
                T.innerHTML = /*html*/ `<tr>
                <th>Name</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Messenger</th>
                <th>Description</th>
                <th>Working</th>
                <th>Options</th>
            </tr>`;
                for (const data of datas.data) {
                    T.innerHTML += /*html*/ `<tr>
                    <td>${data.name}</td>
                    <td>${data.contact_number}</td>
                    <td>${data.email}</td>
                    <td>${data.messenger_id}</td>
                    <td>${data.description}</td>
                    <td><input type="checkbox" disabled${data.working ? ' checked' : ''}/></td>
                    <td><div>
                        <button>&#xe2b4;</button>
                        <button>&#xf304;</button>
                    </div></td>
                </tr>`;
                }
                T.innerHTML += /*html*/ `<tr class="empty">
                <td colspan="99999">Empty</th>
            </tr>`;
                q('#body', x => x.appendChild(T));
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
        yield new Promise(res => setTimeout(res, (d1 - d0) % 500));
        document.body.classList.remove('load');
        document.body.classList.remove('loading');
    });
}
function dom_save(dom) {
    return __awaiter(this, void 0, void 0, function* () {
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
        const res = yield api(Object.assign({ new: 'employee' }, data));
        if (!res.errno) {
            par.classList.remove('edit');
            par.children.map(x => x.removeAttribute('contenteditable'));
        }
    });
}
q('#new', x => x.onclick = () => {
    if (path[0] != 'orders') {
        const R = document.createElement('tbody');
        R.innerHTML = /*html*/ `<tr class="edit">
            <td contenteditable="true"></td>
            <td contenteditable="true"></td>
            <td contenteditable="true"></td>
            <td contenteditable="true"></td>
            <td contenteditable="true"></td>
            <td><input type="checkbox"/></td>
            <td class="edit"><div>
                <button>&#xf329;</button>
                <button>&#xe2b4;</button>
                <button onclick="dom_save(this)">&#xf0c7;</button>
            </div></td>
        </tr>`;
        q('#body>table', y => y.insertBefore(R, q('#body>table>tbody:last-child')[0]));
    }
});
onload = function () {
    return __awaiter(this, void 0, void 0, function* () {
        // Load url determined page
        yield load();
        // Remove load state
        q('.t20p_title', x => x.beginElement());
        yield new Promise(res => setTimeout(res, 500));
    });
};
