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

onload = async function() {
    await new Promise(res => setTimeout(res, 500));
    document.body.classList.remove('load');
    q<SVGAnimateElement>('.t20p_title').map(x=>x.beginElement());
    await new Promise(res => setTimeout(res, 500));
    q('#side>button').map(x=>x.onclick = () => {
        page(x.getAttribute('data')??'');
    });
    page('new-return');
}

/*function start(name) {
    Array.from(document.getElementsByClassName('t20p_'+name)).map(x=>x.beginElement());
}
function pause() {
    document.getElementsByTagName('svg')[0].pauseAnimations();
}
function play() {
    document.getElementsByTagName('svg')[0].unpauseAnimations();
}
function stop() {
    const dom = document.getElementsByTagName('svg')[0];
    const clone = dom.cloneNode(true);
    dom.parentNode.replaceChild(clone, dom);
    /*clone.beginElement();*
}*/