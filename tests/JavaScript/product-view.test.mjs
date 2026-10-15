import test from 'node:test';
import assert from 'node:assert/strict';
import { productView } from '../../resources/js/product-view.js';

function state(reduced = false) {
    globalThis.window = { matchMedia: () => ({ matches: reduced }) };
    let plays = 0, pauses = 0;
    const view = productView({ gallery: [{url:'photo.jpg',alt:'Foto',type:'image'}, {url:'clip.mp4',alt:'Video',type:'video'}], fallbackImage:'fallback.jpg', productName:'Producto', unitPrice:25000 });
    view.$refs = { backgroundVideo: { pause(){pauses++;}, play(){plays++;return Promise.resolve();} } };
    const ticks=[];view.$nextTick = callback => ticks.push(callback);
    return {view, flush:()=>ticks.splice(0).forEach(f=>f()), counts:()=>({plays,pauses})};
}

test('changing video to image pauses the background and updates the source', () => {
    const {view,flush,counts}=state();
    view.setActive(1);flush();assert.equal(counts().plays,1);
    view.setActive(0);flush();assert.equal(view.activeMedia,'photo.jpg');assert.equal(counts().plays,1);assert.equal(counts().pauses,2);
});

test('reduced motion and an open lightbox prevent background autoplay', () => {
    const reduced=state(true);reduced.view.setActive(1);reduced.flush();assert.equal(reduced.counts().plays,0);
    const lightbox=state();lightbox.view.lightboxOpen=true;lightbox.view.setActive(1);lightbox.flush();assert.equal(lightbox.counts().plays,0);
});

test('an empty gallery uses the supplied image and ignores invalid selection', () => {
    const view=productView({fallbackImage:'cover.jpg',productName:'Producto',unitPrice:25000});
    view.setActive(99);view.showNext();view.showPrevious();assert.equal(view.activeMedia,'cover.jpg');assert.equal(view.activeType,'image');
});

 test('a video at the start of the gallery remains the initial media', () => {
    const view=productView({gallery:[{url:'clip.mp4',type:'video',alt:'Video'},{url:'photo.jpg',type:'image',alt:'Foto'}],fallbackImage:'cover.jpg',productName:'Producto',unitPrice:25000});
    assert.equal(view.activeType,'video');assert.equal(view.activeMedia,'clip.mp4');
});
