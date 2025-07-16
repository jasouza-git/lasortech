var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
function q(code) {
    return Array.from(document.querySelectorAll(code));
}
function animate(name) {
    q(`.t20p_${name}`).map(x => x.beginElement());
}
function page(name) {
    q('#side>.on, body>.on').map(x => x.classList.remove('on'));
    q(`#side>[data="${name}"], body>[data="${name}"]`).map(x => x.classList.add('on'));
}
onload = function () {
    return __awaiter(this, void 0, void 0, function* () {
        yield new Promise(res => setTimeout(res, 500));
        document.body.classList.remove('load');
        q('.t20p_title').map(x => x.beginElement());
        yield new Promise(res => setTimeout(res, 500));
        q('#side>button').map(x => x.onclick = () => {
            var _a;
            page((_a = x.getAttribute('data')) !== null && _a !== void 0 ? _a : '');
        });
        page('new-return');
    });
};
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
