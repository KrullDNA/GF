(async()=>{
const BASE=location.origin+'/wp-content/plugins/kdna-forms/assets/js/dist/';
const M=[
["281.05f0bafa828833bb53a5.min.js", 5974],
["653.c5a3f4ea5996f84cb182.min.js", 14893],
["881.845bad12ea15f8451e55.min.js", 8538],
["91.5577381a87a6ada339c1.min.js", 5730],
["940.78d83617d10157680121.min.js", 10473],
["968.b0225a15ff5469a56407.min.js", 379144],
["admin-components.min.js", 571367],
["kform-image-choice.8c715d9df63716be51ac.min.js", 745],
["kform-pagination.8bf1914e463218db3a59.min.js", 5962],
["kform-products.3f164e78d1e7aa5501b2.min.js", 2898],
["libraries.min.js", 383603],
["react-utils.min.js", 47238],
["scripts-admin.block-editor.ac00fe17ae6eec16fde7.min.js", 5355],
["scripts-admin.editor-button.c4d64aa0864322512862.min.js", 4070],
["scripts-admin.embed-form.2b167a2b1fef5cdeea8a.min.js", 17508],
["scripts-admin.field-map.b7aeae1b55c5dab2229c.min.js", 15906],
["scripts-admin.form-ajax-save.d5be798dd293e6405913.min.js", 414],
["scripts-admin.form-editor.3501ffb814082667f9e9.min.js", 28823],
["scripts-admin.form-switcher.7d8eedf956e251813228.min.js", 2670],
["scripts-admin.merge-tags.2d2e81ebce111fb03688.min.js", 157],
["scripts-admin.min.js", 43043],
["scripts-admin.my-feature-thing.3bcbe7fdbe176e3569f5.min.js", 6982],
["scripts-admin.post-select.18c2a2329b01d7198c56.min.js", 994],
["scripts-admin.setup-wizard-Screen01.5ccdbd43f905404f2c0d.min.js", 9423],
["scripts-admin.setup-wizard-Screen02.37b49c33754a2b51d297.min.js", 3314],
["scripts-admin.setup-wizard-Screen03.dbb9335b2a6c0414856e.min.js", 4398],
["scripts-admin.setup-wizard-Screen04.b2e9396a86ab44c813d7.min.js", 3805],
["scripts-admin.setup-wizard-Screen05.dc01f845dbcadb60b968.min.js", 2146],
["scripts-admin.setup-wizard-setup-wizard.677a751666edbd9b3165.min.js", 2258],
["scripts-admin.setup-wizard-store.f76ff48eeb3faa61ce47.min.js", 2085],
["scripts-admin.setup-wizard.418638f64a0f342fdf60.min.js", 410],
["scripts-admin.splash-page.6d42766d5b40918f4501.min.js", 880],
["scripts-admin.system-report.3f1177ed72a34da1a8d4.min.js", 1055],
["scripts-admin.template-library-store.e11e04e58be5d3c6ecf2.min.js", 1419],
["scripts-admin.template-library-template-library-flyout.e456364c3768ae5e8bc5.min.js", 3863],
["scripts-admin.template-library-template-library-grid.d61b882dc5003aa6c851.min.js", 2512],
["scripts-admin.template-library-template-library.f169bace5fae0433b95f.min.js", 4462],
["scripts-admin.template-library-utils.70b328d2fead9632b05f.min.js", 1515],
["scripts-admin.template-library.7e9ccd0d39d17293007f.min.js", 751],
["scripts-admin.user-select.a21483d3ca623b62adac.min.js", 1024],
["scripts-theme.min.js", 41863],
["utils.min.js", 60338],
["vendor-admin.min.js", 46461],
["vendor-theme-dompurify.58fb66e2d47e35c727b6.min.js", 20911],
["vendor-theme.min.js", 20656]
];
const probe=async(n)=>{try{
 const r=await fetch(BASE+n+'?probe='+Date.now(),{cache:'no-store',credentials:'omit'});
 const t=await r.text();
 return{file:n,status:r.status,type:(r.headers.get('content-type')||'').split(';')[0],bytes:t.length,html:/^\s*</.test(t)};
}catch(e){return{file:n,status:'ERR',type:String(e.message||e),bytes:0,html:false};}};
const out=[];
for(const m of M){const p=await probe(m[0]);
 p.expected=m[1];
 p.verdict = p.html ? 'MISSING - server sent HTML'
   : p.status!==200 ? 'HTTP '+p.status
   : p.bytes===m[1] ? 'ok'
   : 'WRONG SIZE';
 out.push(p);}
const ctl=await probe('__kdna_probe_no_such_file__.min.js');
ctl.expected=0; ctl.verdict='CONTROL - this file is meant to be absent';
out.push(ctl);
console.table(out,['file','status','type','bytes','expected','verdict']);
const bad=out.filter(r=>r.verdict==='MISSING - server sent HTML'||r.verdict==='WRONG SIZE'||String(r.verdict).indexOf('HTTP')===0);
console.log('RESULT: '+bad.length+' of '+M.length+' files are not served correctly.');
if(bad.length)console.log(bad.map(r=>r.verdict+'   '+r.file).join('\n'));
console.log('CONTROL absent file -> status '+ctl.status+', type '+ctl.type+', '+ctl.bytes+' bytes, html='+ctl.html);
})();