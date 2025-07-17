function q<T = HTMLElement>(code):T[] {
    return (Array.from(document.querySelectorAll(code)) as T[]);
}
function animate(name:'title'|'loading') {
    q<SVGAnimateElement>(`.t20p_${name}`).map(x=>x.beginElement());
}
function page(name:string) {
    q('#side>.on, body>.on').map(x=>x.classList.remove('on'));
    q(`#side>[data="${name}"], body>[data="${name}"]`).map(x=>x.classList.add('on'));
}
async function read(query:string, mode:string) {
    const par = new URLSearchParams({
        query,
        mode
    })
    const res = await fetch('http://172.16.202.246/api.php').then(res=>res.json());
    console.log(res);
}


onload = async function() {
    //await read('orders', 'all');
    await new Promise(res => setTimeout(res, 500));
    document.body.classList.remove('load');
    q<SVGAnimateElement>('.t20p_title').map(x=>x.beginElement());
    await new Promise(res => setTimeout(res, 500));
    q('#side>button').map(x=>x.onclick = () => {
        page(x.getAttribute('data')??'');
    });
    page('new-return');
}