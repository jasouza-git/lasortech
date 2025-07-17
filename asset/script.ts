// tsc --watch asset/script.ts --outFile script.js --target ES6 --module system --lib DOM,ES6

let path = location.pathname.split('/').slice(1);

/** Generate Query Handler */
function q<T = HTMLElement, R extends unknown = unknown>(code, f:(x:T)=>R = x=>(x as unknown as R)):R[] {
    return (Array.from(document.querySelectorAll(code)) as T[]).map(f);
}
/** Triggers main logo animation */
function animate(name:'title'|'loading') {
    q<SVGAnimateElement>(`.t20p_${name}`, x=>x.beginElement());
}

/** API Response Type */
interface res {
    /** Error Number */
    errno: number,
    /** Error Message */
    error?: string,
    /** Error Reason */
    reason?: string,
    /** Response Data */
    data: null |
        /** Employee */
        {
            id: string 
            name: string,
            contact_number: string,
            email: string,
            messenger_id: string|null,
            avatar: string|null,
            description: string|null,
            working: boolean,
            update_at: Date,
            create_at: Date
        }[]
}

async function read(query:string, mode:string) {
    const par = new URLSearchParams({
        query,
        mode
    })
    const res = await fetch(`/api.php?${par.toString()}`).then(res=>res.json());
    return res as res;
}
async function api(data) {
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(data)) {
        params.append(key, String(value));
    }
    const res = await fetch('/api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params
    }).then(res=>res.json());
    return res as res;
}
/** Loads page */
async function load() {
    // 1. Prepare request
    const d0 = Number(new Date());
    document.body.classList.add('loading');
    const sub:string[] = [path.length > 0 && path[0].length ? path[0] : 'order', path.length > 1 && path[1].length ? path[1] : 'all'];
    // 2. Request to API
    const datas = await read(sub[0], sub[1]);
    const d1 = Number(new Date());
    // 3. Load content
    if (d1-d0 < 500) await new Promise(res => setTimeout(res, 500-(d1-d0)));
    q('#body', x => x.innerHTML = '');
    if (datas.data != null) {
        if (sub[0] == 'orders') {
        } else {
            const T = document.createElement('table');
            T.innerHTML = /*html*/`<tr>
                <th>Name</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Messenger</th>
                <th>Description</th>
                <th>Working</th>
                <th>Options</th>
            </tr>`;
            for (const data of datas.data) {
                T.innerHTML += /*html*/`<tr>
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
            T.innerHTML += /*html*/`<tr class="empty">
                <td colspan="99999">Empty</th>
            </tr>`;
            q('#body', x => x.appendChild(T));
        }
    } else {
        q('#body', x => x.innerHTML = /*html*/`
            <div class="error">
                <h1>${datas.error??'Unknown Error'}${datas.reason ? ': ' : ''}${datas.reason??''}</h1>
                <p>Error code ${datas.errno}</p>
            </div>
        `);
    }
    
    // 4. Remove Animation
    await new Promise(res => setTimeout(res, (d1-d0)%500))
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
    if (!data.messenger_id.length) delete data.messenger_id;
    if (!data.description.length) delete data.description;
    console.log(data);
    const res = await api({new:'employee', ...data});
    if (!res.errno) {
        par.classList.remove('edit');
        par.children.map(x => x.removeAttribute('contenteditable'));
    }
}

q('#new', x => x.onclick = () => {
    if (path[0] != 'orders') {
        const R = document.createElement('tbody');
        R.innerHTML = /*html*/`<tr class="edit">
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
        q('#body>table', y => y.insertBefore(R, q<unknown,HTMLElement>('#body>table>tbody:last-child')[0]));
    }
});

onload = async function() {
    // Load url determined page
    await load();
    // Remove load state
    q<SVGAnimateElement>('.t20p_title', x=>x.beginElement());
    await new Promise(res => setTimeout(res, 500));
}