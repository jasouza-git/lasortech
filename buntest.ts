#!/usr/bin/env -S bun run
/** # LasorTech Server
  * The LasorTech server is the website for LasorTech to promote themselves online, it captures company details and 
  * ### TODO
  * - [ ] Responsive loading *(first loaded and next page loading)*
  *   - [ ] Customizable logo entity *(Switch between default, loading, and title)*
  * - [ ] Responsive browser history *(back button in mobiles and web navigation in browsers)*
  * @module 
  */
const DOCS = 0;
import puppeteer, { Browser, Page } from 'puppeteer';

export class Scrapper {
    userdata:string = 'userdata';
    chrome:string = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    debug:boolean = true;
    browser:Browser|null = null;
    site:Page|null = null;

    constructor() {
        
    }
    async start() {
        this.browser = await puppeteer.launch({
            executablePath: this.chrome,
            headless: !this.debug,
            userDataDir: this.userdata,
        });
        this.site = await this.browser.newPage();
        await this.site.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        await this.site.setViewport({ width: 1280, height: 800 });
        await this.site.emulateTimezone("America/New_York");
        await this.site.evaluateOnNewDocument(() => {
            Object.defineProperty(navigator, "webdriver", { get: () => false });
        });

        await this.site.goto('https://shopee.ph/lasortech_ph');
        
        await this.site.waitForSelector('form');
        console.log(await this.site.title());
        await (new Promise<void>(x=>setTimeout(()=>x(), 10000)));
    }
}

if (import.meta.main) {
    const scrapper = new Scrapper();
    scrapper.start();
}