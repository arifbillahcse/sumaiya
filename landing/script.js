(function(){
  'use strict';
  var $=function(s,c){return (c||document).querySelector(s)};
  var $$=function(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))};

  /* mobile menu */
  var mb=$('.menu-btn'),mn=$('#mobile-nav');
  mb.addEventListener('click',function(){
    var o=mn.classList.toggle('open');mb.setAttribute('aria-expanded',o);
  });
  $$('a',mn).forEach(function(a){a.addEventListener('click',function(){mn.classList.remove('open');mb.setAttribute('aria-expanded',false)})});

  /* reveal on scroll */
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}})},{threshold:.12});
    $$('.reveal').forEach(function(el){io.observe(el)});
  }else{$$('.reveal').forEach(function(el){el.classList.add('in')})}

  /* footer: year + today's hours */
  $('#yr').textContent=new Date().getFullYear();
  var d=new Date().getDay(),t=$('#hours [data-day="'+d+'"]');if(t)t.classList.add('today');

  /* video placeholder: swap in your embed URL */
  $('.play').addEventListener('click',function(){
    var f=$('.video-frame'),src=f.getAttribute('data-src');
    if(src){f.innerHTML='<iframe src="'+src+'" allow="autoplay;fullscreen" style="position:absolute;inset:0;width:100%;height:100%;border:0"></iframe>'}
  });

  /* helpers */
  function validate(form){
    var ok=true;
    $$('[required]',form).forEach(function(i){
      var v=i.value.trim(),bad=!v;
      if(!bad&&i.type==='email')bad=!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v);
      if(!bad&&i.type==='tel')bad=v.replace(/\D/g,'').length<10;
      i.classList.toggle('err',bad);i.setAttribute('aria-invalid',bad);
      if(bad)ok=false;
    });
    if(!ok){var f=$('.err',form);f&&f.focus()}
    return ok;
  }

  /* booking flow */
  var card=$('#bookCard'),panels=$$('.panel',card),steps=$$('.stepper li',card),state={type:'',day:'',time:''};
  function show(n){
    panels.forEach(function(p,i){p.hidden=i!==n});
    steps.forEach(function(s,i){s.classList.toggle('on',i===(n>=2?1:0))});
  }
  $$('[data-type]',card).forEach(function(b){b.addEventListener('click',function(){
    state.type=b.getAttribute('data-type');$('#patientType').textContent=state.type;show(1);
    var f=$('input',panels[1]);f&&f.focus();
  })});
  $$('[data-back]',card).forEach(function(b){b.addEventListener('click',function(){show(+b.getAttribute('data-back'))})});
  panels[1].addEventListener('submit',function(e){e.preventDefault();if(validate(this)){buildDays();show(2)}});

  var TIMES=['8:30 am','9:30 am','10:30 am','11:30 am','1:00 pm','2:00 pm','3:30 pm','4:30 pm','5:30 pm'];
  var hoursByDay={1:[8.5,19],2:[8.5,19],3:[7,18],4:[8.5,19],5:[8.5,13]};
  function toH(s){var m=s.match(/(\d+):(\d+) (am|pm)/),h=+m[1]%12+(m[3]==='pm'?12:0);return h+ +m[2]/60}
  function chip(html,val,group,onPick){
    var b=document.createElement('button');b.type='button';b.className='chip';b.setAttribute('role','radio');b.setAttribute('aria-checked','false');b.innerHTML=html;
    b.addEventListener('click',function(){$$('.chip',group).forEach(function(c){c.setAttribute('aria-checked','false')});b.setAttribute('aria-checked','true');onPick(val)});
    group.appendChild(b);
  }
  function buildDays(){
    var g=$('#days');g.innerHTML='';$('#times').innerHTML='';state.day=state.time='';$('#confirmBtn').disabled=true;
    var n=0,dt=new Date();dt.setDate(dt.getDate()+1);
    while(n<7){
      if(hoursByDay[dt.getDay()]){
        (function(x){
          var label=x.toLocaleDateString('en-US',{weekday:'short'}),sub=x.toLocaleDateString('en-US',{month:'short',day:'numeric'});
          chip(label+'<small>'+sub+'</small>',x.toDateString(),g,function(v){state.day=x.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric'});buildTimes(x.getDay())});
        })(new Date(dt));n++;
      }
      dt.setDate(dt.getDate()+1);
    }
  }
  function buildTimes(dow){
    var g=$('#times');g.innerHTML='';state.time='';$('#confirmBtn').disabled=true;
    var h=hoursByDay[dow];
    TIMES.filter(function(t){var x=toH(t);return x>=h[0]&&x<h[1]-.5}).forEach(function(t){
      chip(t,t,g,function(v){state.time=v;$('#confirmBtn').disabled=false});
    });
  }
  $('#confirmBtn').addEventListener('click',function(){
    var f=$('form',card),name=f.elements.first.value.trim();
    /* TODO: send {state, form values} to your booking backend / Aloha / Formspree here */
    $('#doneMsg').textContent='Thanks, '+name+'! We received your '+state.type.toLowerCase()+' request for '+state.day+' at '+state.time+'. Our team will confirm shortly.';
    show(3);
  });

  /* testimonials slider */
  var slides=$$('.slide'),dots=$('#dots'),cur=0,timer;
  slides.forEach(function(_,i){var b=document.createElement('button');b.setAttribute('aria-label','Review '+(i+1));b.addEventListener('click',function(){go(i,true)});dots.appendChild(b)});
  function go(i,user){
    cur=(i+slides.length)%slides.length;
    slides.forEach(function(s,k){s.classList.toggle('on',k===cur)});
    $$('button',dots).forEach(function(b,k){b.classList.toggle('on',k===cur)});
    if(user){clearInterval(timer);timer=null}
  }
  $('.prev').addEventListener('click',function(){go(cur-1,true)});
  $('.next').addEventListener('click',function(){go(cur+1,true)});
  go(0);
  if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches)timer=setInterval(function(){go(cur+1)},7000);

  /* lead form */
  var lf=$('#leadForm'),msg=$('.form-msg',lf);
  lf.addEventListener('submit',function(e){
    e.preventDefault();msg.className='form-msg';
    if(!validate(lf)){msg.textContent='Please fill in the highlighted fields.';msg.classList.add('bad');return}
    /* TODO: POST new FormData(lf) to your form endpoint (Formspree / Web3Forms / WP admin-ajax) */
    msg.textContent='Thank you! We\'ll be in touch shortly to schedule your $49 StemWave trial.';msg.classList.add('ok');lf.reset();
  });
})();
