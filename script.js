(function(){
  'use strict';
/* ---- SETTINGS: edit here ------------------------------------------
   1. Create a free key at https://web3forms.com (enter the clinic's email).
   2. Paste it below. Leave empty to run in demo mode (no email is sent).
   3. The StemWave video is assets/stemwave-video.mp4 (replace the file to change it). */
var CONFIG={
  WEB3FORMS_KEY:'',
  ENDPOINT:'https://api.web3forms.com/submit',
  FROM_NAME:'Jester Family Chiropractic Website'
};
  var $=function(s,c){return (c||document).querySelector(s)};
  var $$=function(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))};

  /* mobile menu */
  var mb=$('.menu-btn'),mn=$('#mobile-nav');
  if(mb&&mn){
    mb.addEventListener('click',function(){var o=mn.classList.toggle('open');mb.setAttribute('aria-expanded',o)});
    $$('a',mn).forEach(function(a){a.addEventListener('click',function(){mn.classList.remove('open');mb.setAttribute('aria-expanded',false)})});
  }

  /* header shadow on scroll */
  var hdr=$('.site-header');
  if(hdr){var onS=function(){hdr.classList.toggle('scrolled',window.scrollY>10)};onS();window.addEventListener('scroll',onS,{passive:true})}

  /* full-bleed hero when assets/hero.jpg is provided */
  var hero=$('.hero-split');
  if(hero){var hi=new Image();hi.onload=function(){hero.style.setProperty('--hero','url(assets/hero.jpg)');hero.classList.add('hero-full')};hi.src='assets/hero.jpg'}

  /* reveal on scroll */
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}})},{threshold:.12});
    $$('.reveal').forEach(function(el){io.observe(el)});
  }else{$$('.reveal').forEach(function(el){el.classList.add('in')})}

  /* hide social icons until real URLs are set in the HTML */
  $$('.social a').forEach(function(a){if(a.getAttribute('href')==='#')a.style.display='none'});
  var so=$('.social');if(so&&!$$('a',so).some(function(a){return a.style.display!=='none'})){so.style.display='none'}

  /* footer: year + today's hours */
  var yr=$('#yr');if(yr)yr.textContent=new Date().getFullYear();
  var t=$('#hours [data-day="'+new Date().getDay()+'"]');if(t)t.classList.add('today');

  /* StemWave video: custom play button over the first frame; native controls once playing */
  var frame=$('.video-frame'),vid=$('.sw-video'),play=$('.play');
  if(frame&&vid&&play){
    var start=function(){
      frame.classList.add('playing');vid.controls=true;
      var p=vid.play();if(p&&p.catch)p.catch(function(){frame.classList.remove('playing');vid.controls=false});
    };
    play.addEventListener('click',start);
    vid.addEventListener('click',function(){if(!vid.controls)start()});
    vid.addEventListener('ended',function(){frame.classList.remove('playing');vid.controls=false;vid.currentTime=0.1});
    /* pause other media if the user scrolls far past: keep it simple, pause when tab hidden */
    document.addEventListener('visibilitychange',function(){if(document.hidden&&!vid.paused)vid.pause()});
  }

  /* validation */
  function validate(form){
    var ok=true;
    $$('[required]',form).forEach(function(i){
      var v=i.value.trim(),bad=i.type==='checkbox'?!i.checked:!v;
      if(!bad&&i.type==='email')bad=!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v);
      if(!bad&&i.type==='tel')bad=v.replace(/\D/g,'').length<10;
      i.classList.toggle('err',bad);i.setAttribute('aria-invalid',bad);
      if(bad)ok=false;
    });
    if(!ok){var f=$('.err',form);f&&f.focus()}
    return ok;
  }


  /* send to Web3Forms (or demo mode when no key) */
  function send(fields,subject){
    if(!CONFIG.WEB3FORMS_KEY){return new Promise(function(r){setTimeout(function(){console.info('[demo] form not sent (no WEB3FORMS_KEY):',fields);r(true)},400)})}
    var body=Object.assign({access_key:CONFIG.WEB3FORMS_KEY,subject:subject,from_name:CONFIG.FROM_NAME},fields);
    return fetch(CONFIG.ENDPOINT,{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(body)})
      .then(function(r){return r.json()}).then(function(j){return !!j.success}).catch(function(){return false});
  }
  function formData(f){
    var o={};$$('input,textarea',f).forEach(function(i){if(!i.name)return;o[i.name]=i.type==='checkbox'?(i.checked?'Yes':'No'):i.value.trim()});return o;
  }

  /* generic lead / contact forms */
  $$('form.js-form').forEach(function(f){
    var msg=$('.form-msg',f),btn=$('button[type=submit]',f);
    f.addEventListener('submit',function(e){
      e.preventDefault();msg.className='form-msg';
      if(!validate(f)){msg.textContent='Please complete the highlighted fields.';msg.classList.add('bad');return}
      var d=formData(f);if(d.botcheck==='Yes')return;delete d.botcheck;
      btn.disabled=true;msg.textContent='Sending…';msg.classList.add('sending');
      send(d,f.getAttribute('data-subject')||'Website form').then(function(ok){
        btn.disabled=false;msg.className='form-msg';
        if(ok){msg.textContent=f.getAttribute('data-ok')||'Thank you!';msg.classList.add('ok');f.reset()}
        else{msg.textContent='Something went wrong. Please call us at 610.696.6676.';msg.classList.add('bad')}
      });
    });
  });

  /* testimonials slider */
  var slider=$('#slider');
  if(slider){
    var slides=$$('.slide'),dots=$('#dots'),cur=0,timer;
    slides.forEach(function(_,i){var b=document.createElement('button');b.setAttribute('aria-label','Review '+(i+1));b.addEventListener('click',function(){go(i,true)});dots.appendChild(b)});
    var go=function(i,user){
      cur=(i+slides.length)%slides.length;
      slides.forEach(function(s,k){s.classList.toggle('on',k===cur)});
      $$('button',dots).forEach(function(b,k){b.classList.toggle('on',k===cur)});
      if(user&&timer){clearInterval(timer);timer=null}
    };
    /* swipe on touch screens */
    var tx=null;
    slider.addEventListener('touchstart',function(e){tx=e.touches[0].clientX},{passive:true});
    slider.addEventListener('touchend',function(e){
      if(tx===null)return;var dx=e.changedTouches[0].clientX-tx;tx=null;
      if(Math.abs(dx)>40)go(cur+(dx<0?1:-1),true);
    },{passive:true});
    $('.prev').addEventListener('click',function(){go(cur-1,true)});
    $('.next').addEventListener('click',function(){go(cur+1,true)});
    go(0);
    if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches)timer=setInterval(function(){go(cur+1)},7000);
  }

  /* booking flow (book.html) */
  var card=$('#bookCard');
  if(card){
    var panels=$$('.panel',card),steps=$$('.stepper li',card),state={type:'',day:'',time:'',service:''};
    var svc=new URLSearchParams(location.search).get('service');
    if(svc==='stemwave'){state.service='StemWave $49 trial';$('#serviceBadge').hidden=false}

    var show=function(n){
      panels.forEach(function(p,i){p.hidden=i!==n});
      steps.forEach(function(s,i){s.classList.toggle('on',i===(n>=2?1:0))});
    };
    $$('[data-type]',card).forEach(function(b){b.addEventListener('click',function(){
      state.type=b.getAttribute('data-type');$('#patientType').textContent=state.type;show(1);
      var f=$('input',panels[1]);f&&f.focus();
    })});
    $$('[data-back]',card).forEach(function(b){b.addEventListener('click',function(){show(+b.getAttribute('data-back'))})});
    panels[1].addEventListener('submit',function(e){e.preventDefault();if(validate(this)){buildDays();show(2)}});

    var TIMES=['8:30 am','9:30 am','10:30 am','11:30 am','1:00 pm','2:00 pm','3:30 pm','4:30 pm','5:30 pm'];
    var hoursByDay={1:[8.5,19],2:[8.5,19],3:[7,18],4:[8.5,19],5:[8.5,13]};
    var toH=function(s){var m=s.match(/(\d+):(\d+) (am|pm)/);return (+m[1]%12)+(m[3]==='pm'?12:0)+ +m[2]/60};
    var chip=function(html,group,onPick){
      var b=document.createElement('button');b.type='button';b.className='chip';b.setAttribute('role','radio');b.setAttribute('aria-checked','false');b.innerHTML=html;
      b.addEventListener('click',function(){$$('.chip',group).forEach(function(c){c.setAttribute('aria-checked','false')});b.setAttribute('aria-checked','true');onPick()});
      group.appendChild(b);
    };
    var confirmBtn=$('#confirmBtn');
    var buildDays=function(){
      var g=$('#days');g.innerHTML='';$('#times').innerHTML='';state.day=state.time='';confirmBtn.disabled=true;
      var n=0,dt=new Date();dt.setDate(dt.getDate()+1);
      while(n<7){
        if(hoursByDay[dt.getDay()]){
          (function(x){
            chip(x.toLocaleDateString('en-US',{weekday:'short'})+'<small>'+x.toLocaleDateString('en-US',{month:'short',day:'numeric'})+'</small>',g,function(){
              state.day=x.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric'});buildTimes(x.getDay());
            });
          })(new Date(dt));n++;
        }
        dt.setDate(dt.getDate()+1);
      }
    };
    var buildTimes=function(dow){
      var g=$('#times');g.innerHTML='';state.time='';confirmBtn.disabled=true;
      var h=hoursByDay[dow];
      TIMES.filter(function(t){var x=toH(t);return x>=h[0]&&x<h[1]-.5}).forEach(function(t){
        chip(t,g,function(){state.time=t;confirmBtn.disabled=false});
      });
    };
    confirmBtn.addEventListener('click',function(){
      var f=$('form',card),d=formData(f),name=d.first;
      d.patient_type=state.type;d.service=state.service||'General appointment';d.requested_day=state.day;d.requested_time=state.time;
      confirmBtn.disabled=true;confirmBtn.textContent='Sending…';
      send(d,'Appointment request: '+d.service+' ('+state.type+')').then(function(ok){
        confirmBtn.textContent='Request Appointment';confirmBtn.disabled=false;
        if(!ok){alert('Sorry, we could not send your request. Please call 610.696.6676.');return}
        $('#doneMsg').textContent='Thanks, '+name+'! We received your '+(state.service?state.service+' ':'')+state.type.toLowerCase()+' request for '+state.day+' at '+state.time+'. Our team will confirm shortly.';
        show(3);
      });
    });
  }
})();
