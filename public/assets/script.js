/* Meu Treino II: evolução do frontend existente. Dados externos usam textContent. */
'use strict';
const nomes={peito:'Peito',costas:'Costas',pernas:'Pernas',ombros:'Ombros',bracos:'Bíceps',triceps:'Tríceps',abdomen:'Abdômen'};
const tempos={'30':'≈ 30 minutos','60':'≈ 1 hora','90':'Mais de 1 hora'};
const $=id=>document.getElementById(id);
let token=document.querySelector('meta[name="csrf-token"]').content;
function mensagem(text,error=false){const b=$('mensagem');b.textContent=text;b.classList.toggle('error',error);b.hidden=false;b.focus();}
async function api(resource,method='GET',data=null,params={}){
 const query=new URLSearchParams({resource,...params}),options={method,credentials:'same-origin',headers:{Accept:'application/json'}};
 if(method!=='GET')options.headers['X-CSRF-Token']=token;
 if(data!==null){options.headers['Content-Type']='application/json';options.body=JSON.stringify(data);}
 let response;try{response=await fetch('api.php?'+query,options);}catch(e){throw new Error('Não foi possível conectar. Verifique se o Apache está iniciado.');}
 let result;try{result=await response.json();}catch(e){throw new Error('Resposta inesperada do servidor. Confira PHP, banco e logs.');}
 if(!response.ok){
  if(response.status===401 && !['login','cadastro'].includes(document.body.dataset.view)){location.href='index.php?view=login';throw new Error('Sessão encerrada.');}
  const labels={nome:'Nome',email:'E-mail',senha:'Senha',series:'Séries',repeticoes:'Repetições',carga:'Carga',observacao:'Observação',exercicio_id:'Exercício',grupo_muscular:'Grupo muscular',descricao:'Descrição',video_url:'Vídeo',nivel:'Nível',objetivo:'Objetivo',tempo_disponivel:'Tempo',tempo:'Tempo',id:'Registro',page:'Página'};
  const details=Object.entries(result.errors||{}).filter(([k])=>k!=='debug').map(([k,v])=>`${labels[k]||'Campo'}: ${v}`);
  throw new Error([result.message,...details].join('\n'));
 }return result;
}
function node(tag,text,className){const e=document.createElement(tag);if(text!==undefined)e.textContent=text;if(className)e.className=className;return e;}
function fmtDate(v){if(!v)return 'Sem registro';const iso=v.includes('T')?v:v.replace(' ','T')+'-03:00';return new Date(iso).toLocaleString('pt-BR',{timeZone:'America/Sao_Paulo',dateStyle:'short',timeStyle:'short'});}
function formData(f){return Object.fromEntries(new FormData(f));}
function bindForm(id,action){const f=$(id);if(!f)return;f.addEventListener('submit',async event=>{event.preventDefault();const b=f.querySelector('[type="submit"]');if(b.disabled)return;b.disabled=true;try{await action(f);}catch(e){mensagem(e.message,true);}finally{b.disabled=false;}});}
function actionButton(text,action,danger=false){const b=node('button',text,'button small '+(danger?'danger':'secondary'));b.type='button';b.addEventListener('click',async()=>{b.disabled=true;try{await action();}catch(e){mensagem(e.message,true);}finally{b.disabled=false;}});return b;}
function table(headers,rows){const wrap=node('div',undefined,'table-wrap'),t=node('table'),head=node('thead'),hr=node('tr');for(const h of headers){const th=node('th',h);th.scope='col';hr.append(th);}head.append(hr);t.append(head);const body=node('tbody');for(const row of rows){const tr=node('tr');for(const cell of row){const td=node('td');td.append(cell instanceof Node?cell:document.createTextNode(String(cell??'')));tr.append(td);}body.append(tr);}t.append(body);wrap.append(t);return wrap;}
function videoLink(url){try{const u=new URL(url);if(u.protocol!=='https:'||u.username||u.password)return null;const a=node('a','▶ Ver como fazer','video-link');a.href=u.href;a.target='_blank';a.rel='noopener noreferrer';return a;}catch{return null;}}
function workoutList(items,editable=false,onDelete=null){
 if(!items.length)return node('p','Você ainda não tem registros aqui. Use “Registrar treino” para começar.','empty');
 const list=node('div',undefined,'history-list');
 for(const t of items){const card=node('article',undefined,'history-card');const header=node('div',undefined,'section-heading');const title=node('div');title.append(node('p',fmtDate(t.data_treino),'small-text'),node('h3',nomes[t.grupo_muscular]));header.append(title);
  if(editable){const actions=node('div',undefined,'actions');const a=node('a','Editar','button secondary small');a.href='index.php?view=registrar&id='+encodeURIComponent(t.id);actions.append(a,actionButton('Excluir',async()=>{if(!confirm('Excluir este treino de '+nomes[t.grupo_muscular]+'?'))return;await api('treinos','DELETE',null,{id:t.id});mensagem('Treino excluído. O painel será recalculado com os registros restantes.');await onDelete();},true));header.append(actions);}card.append(header);
  const ul=node('ul',undefined,'history-exercises');for(const e of t.exercicios){const li=node('li',e.nome||e.descricao_livre);const details=[];if(e.series!==null)details.push(e.series+' séries');if(e.repeticoes!==null)details.push(e.repeticoes+' repetições');if(e.carga!==null)details.push(Number(e.carga).toLocaleString('pt-BR')+' kg');if(details.length)li.append(node('small',details.join(' · ')));ul.append(li);}card.append(ul);if(t.observacao)card.append(node('p',t.observacao,'small-text'));list.append(card);
 }return list;
}
async function painel(){
 const [summary,history]=await Promise.all([api('resumo'),api('treinos')]);
 $('total').textContent=history.data.total;const last=history.data.items[0];$('ultimo').textContent=last?fmtDate(last.data_treino):'Ainda não há';$('ultimo-nome').textContent=last?nomes[last.grupo_muscular]:'Seu primeiro registro começa aqui';
 const clockOffset=new Date(summary.data.agora).getTime()-Date.now();
 function renderRest(){let week=0;$('lista-semana').replaceChildren();for(const r of summary.data.grupos){const key=r.grupo_muscular;const rest=r.disponivel_em && (Date.now()+clockOffset)<new Date(r.disponivel_em).getTime();$('status-'+key).textContent=r.ultimo?(rest?'Em descanso':'Pode treinar novamente'):'Pode treinar';$('status-'+key).classList.toggle('rest',!!rest);$('data-'+key).textContent=r.ultimo?'Último treino: '+fmtDate(r.ultimo):'Ainda sem treino registrado.';$('disponivel-'+key).textContent=r.disponivel_em?'Disponível novamente: '+fmtDate(r.disponivel_em):'Registre quando terminar seu treino.';if(r.treinado_semana)week++;$('lista-semana').append(node('li',r.nome+(r.treinado_semana?' · Treinado ✓':' · Ainda sem registro'),r.treinado_semana?'done':''));}$('semana-total').textContent=week+' / 7';}
 renderRest();setInterval(renderRest,30000);$('recentes').replaceChildren(workoutList(history.data.items.slice(0,3)));
}
// Checkbox e detalhes opcionais por exercício. Nenhum campo numérico é obrigatório.
function checkOption(ex,checked=false){const label=node('label',undefined,'exercise-check');const box=node('input');box.type='checkbox';box.value=String(ex.id);box.checked=checked;label.append(box,node('span',ex.nome));return {label,box};}
function detailsBlock(key,name,values={}){const wrap=node('div',undefined,'item-detail');wrap.dataset.key=key;wrap.append(node('h3',name));const grid=node('div',undefined,'form-grid three');for(const [field,label,min,max,step] of [['series','Séries',1,30,1],['repeticoes','Repetições',1,1000,1],['carga','Carga (kg)',0,1000,.01]]){const d=node('div'),l=node('label',label),input=node('input');input.id='d-'+key+'-'+field;l.htmlFor=input.id;input.type='number';input.min=min;input.max=max;input.step=step;input.value=values[field]??'';input.dataset.field=field;d.append(l,input);grid.append(d);}wrap.append(grid);return wrap;}
function readDetails(key){const d=document.querySelector('.item-detail[data-key="'+key+'"]');const result={};if(d)for(const input of d.querySelectorAll('input'))result[input.dataset.field]=input.value===''?null:input.value;return result;}
async function registrar(){
 const params=new URLSearchParams(location.search),id=params.get('id');
 const exercises=(await api('exercicios')).data,group=$('grupo');let existing=null;
 if(id){existing=(await api('treinos','GET',null,{id})).data;group.value=existing.grupo_muscular;$('form-title').textContent='Editar treino';$('salvar').textContent='Salvar alterações';$('registro-horario').textContent='Data original preservada: '+fmtDate(existing.data_treino);$('observacao').value=existing.observacao;}
 else if(nomes[params.get('grupo')])group.value=params.get('grupo');
 const selected=new Map();
 function rebuildDetails(){const keep=new Map();for(const el of $('item-details').children)keep.set(el.dataset.key,readDetails(el.dataset.key));$('item-details').replaceChildren();for(const [key,ex] of selected)$('item-details').append(detailsBlock(key,ex.nome,keep.get(key)||ex.values||{}));if($('outro').value.trim())$('item-details').append(detailsBlock('livre','Outro exercício',keep.get('livre')||freeValues));}
 let freeValues={};
 function populate(restore=false){selected.clear();$('exercise-options').replaceChildren();$('item-details').replaceChildren();
  for(const ex of exercises.filter(e=>e.grupo_muscular===group.value)){const previous=restore?existing?.exercicios.find(i=>String(i.exercicio_id)===String(ex.id)):null;const {label,box}=checkOption(ex,!!previous);if(previous)selected.set(String(ex.id),{...ex,values:previous});box.addEventListener('change',()=>{if(box.checked)selected.set(String(ex.id),ex);else selected.delete(String(ex.id));rebuildDetails();});$('exercise-options').append(label);}
  if(!$('exercise-options').children.length)$('exercise-options').append(node('p','Nenhum exercício neste grupo. Você pode descrever o que fez abaixo.'));
  rebuildDetails();
 }
 if(existing){const free=existing.exercicios.filter(i=>i.exercicio_id===null);$('outro').value=free.map(i=>i.descricao_livre).join('\n');freeValues=free[0]||{};}
 populate(true);group.addEventListener('change',()=>{freeValues={};populate(false);});$('outro').addEventListener('change',rebuildDetails);
 if(existing?.exercicios.some(i=>i.series!==null||i.repeticoes!==null||i.carga!==null)||existing?.observacao)$('detalhes-treino').open=true;
 $('salvar').disabled=false;
 bindForm('treino-form',async()=>{const items=[...selected.keys()].map(key=>({exercicio_id:Number(key),...readDetails(key)}));const free=$('outro').value.trim();if(free)items.push({descricao_livre:free,...readDetails('livre')});if(!items.length)throw new Error('Marque um exercício ou escreva o que fez.');await api('treinos',id?'PUT':'POST',{grupo_muscular:group.value,exercicios:items,observacao:$('observacao').value,confirmado:true},id?{id}:{});location.href='index.php?view=treinos&saved=1';});
}
async function montar(){
 const params=new URLSearchParams(location.search);if(nomes[params.get('grupo')])$('grupo').value=params.get('grupo');
 const profile=(await api('perfil')).data;$('tempo').value=profile.tempo_disponivel;
 let suggestion=null;
 async function generate(){const r=await api('sugestao','GET',null,{grupo_muscular:$('grupo').value,tempo:$('tempo').value});suggestion=r.data;$('confirmacao').hidden=true;$('sugestao-result').hidden=false;$('sugestao-title').textContent='Treino sugerido — '+nomes[suggestion.grupo_muscular];const o=suggestion.orientacao;$('sugestao-tempo').textContent=tempos[suggestion.tempo]+' · '+o.nivel_label+' · '+o.objetivo_label+' — Referência: '+o.series+' · '+o.repeticoes+' · descanso '+o.descanso;$('sugestao-exercicios').replaceChildren();for(const [i,e] of suggestion.exercicios.entries()){const card=node('article',undefined,'suggestion-exercise');card.append(node('h3',(i+1)+'. '+e.nome));const descricao=(e.descricao||'').trim();const interna=/^(Opção do catálogo|Sugestão presente no Trabalho I)/i.test(descricao);if(descricao&&!interna)card.append(node('p',descricao,'small-text'));const a=videoLink(e.video_url);if(a)card.append(a);$('sugestao-exercicios').append(card);}$('sugestao-aviso').textContent=suggestion.exercicios.length<suggestion.quantidade_alvo?'O catálogo tem '+suggestion.exercicios.length+' exercício(s) disponível(is) para este grupo. Não repetimos exercícios para completar a quantidade.':suggestion.aviso;$('registrar-sugestao').disabled=!suggestion.exercicios.length;}
 bindForm('sugestao-form',generate);$('montar-botao').disabled=false;
 $('outra-sugestao').addEventListener('click',async()=>{const b=$('outra-sugestao');b.disabled=true;try{await generate();}catch(e){mensagem(e.message,true);}finally{b.disabled=false;}});
 $('registrar-sugestao').addEventListener('click',()=>{if(!suggestion)return;$('confirmar-exercicios').replaceChildren();for(const e of suggestion.exercicios)$('confirmar-exercicios').append(checkOption(e,true).label);$('confirmacao').hidden=false;$('confirmacao').scrollIntoView({behavior:'smooth',block:'start'});$('confirmar-exercicios').querySelector('input')?.focus();});
 $('cancelar-confirmacao').addEventListener('click',()=>{$('confirmacao').hidden=true;});
 for(const id of ['grupo','tempo'])$(id).addEventListener('change',()=>{suggestion=null;$('sugestao-result').hidden=true;$('confirmacao').hidden=true;});
 bindForm('confirmar-form',async()=>{if(!suggestion)throw new Error('Monte uma sugestão primeiro.');const items=[...$('confirmar-exercicios').querySelectorAll('input:checked')].map(b=>({exercicio_id:Number(b.value)}));if(!items.length)throw new Error('Marque pelo menos um exercício que você realizou.');await api('treinos','POST',{grupo_muscular:suggestion.grupo_muscular,exercicios:items,confirmado:true});location.href='index.php?view=treinos&saved=1';});
}
async function perfil(){const d=(await api('perfil')).data;for(const key of ['nivel','objetivo','tempo_disponivel'])$(key).value=d[key];$('perfil-form').querySelector('button').disabled=false;bindForm('perfil-form',async f=>{await api('perfil','PUT',formData(f));mensagem('Preferências salvas.');});}
let historyPage=1;
async function historico(){const d=(await api('treinos','GET',null,{page:historyPage})).data;if(historyPage>1&&!d.items.length){historyPage--;return historico();}$('historico-total').textContent=d.total+' treino(s) confirmado(s)';$('historico').replaceChildren(workoutList(d.items,true,historico));$('pagina').textContent=`${historyPage} / ${Math.max(1,Math.ceil(d.total/d.per_page))}`;$('anterior').disabled=historyPage<=1;$('proxima').disabled=historyPage*d.per_page>=d.total;}
let usersPage=1;
async function users(){const d=(await api('usuarios','GET',null,{page:usersPage})).data;$('usuarios').replaceChildren(table(['Nome','E-mail','Perfil','Cadastro'],d.items.map(u=>[u.nome,u.email,u.perfil,u.criado_em])));$('users-page').textContent=`${usersPage} / ${Math.max(1,Math.ceil(d.total/20))}`;$('users-prev').disabled=usersPage<=1;$('users-next').disabled=usersPage*20>=d.total;}
function clearExercise(){$('exercicio-form').reset();$('exercise-id').value='';$('exercise-title').textContent='Cadastrar exercício';}
async function catalogo(){const data=(await api('exercicios')).data;const rows=data.map(e=>{const a=node('div',undefined,'actions');a.append(actionButton('Editar',async()=>{$('exercise-id').value=e.id;for(const key of ['nome','grupo_muscular','descricao','video_url'])$(key).value=e[key];$('exercise-title').textContent='Editar exercício';$('nome').focus();}),actionButton('Excluir',async()=>{if(!confirm('Excluir '+e.nome+'?'))return;await api('exercicios','DELETE',null,{id:e.id});clearExercise();mensagem('Exercício excluído.');await Promise.all([catalogo(),stats()]);},true));return[e.nome,nomes[e.grupo_muscular],e.descricao||'—',videoLink(e.video_url)||'Não informado',a];});$('catalogo').replaceChildren(rows.length?table(['Nome','Grupo','Descrição','Vídeo','Ações'],rows):node('p','Nenhum exercício cadastrado.'));}
async function stats(){const d=(await api('estatisticas')).data;$('admin-stats').replaceChildren();for(const [key,label] of Object.entries({usuarios:'Usuários',exercicios:'Exercícios',registros:'Treinos confirmados'})){const a=node('article');a.append(node('p',label),node('strong',d[key]));$('admin-stats').append(a);}}
async function init(){
 $('logout')?.addEventListener('click',async()=>{try{await api('logout','POST',{});location.href='index.php?view=login';}catch(e){mensagem(e.message,true);}});
 bindForm('login-form',async f=>{await api('login','POST',formData(f));location.href='index.php';});bindForm('cadastro-form',async f=>{await api('cadastro','POST',formData(f));location.href='index.php?view=login&created=1';});
 const view=document.body.dataset.view,params=new URLSearchParams(location.search);
 if(view==='login'&&params.get('created'))mensagem('Conta criada! Entre com seu e-mail e senha.');
 if(view==='painel')await painel();if(view==='registrar')await registrar();if(view==='montar')await montar();if(view==='perfil')await perfil();
 if(view==='treinos'){$('anterior').addEventListener('click',()=>{historyPage--;historico().catch(e=>mensagem(e.message,true));});$('proxima').addEventListener('click',()=>{historyPage++;historico().catch(e=>mensagem(e.message,true));});await historico();if(params.get('saved'))mensagem('Treino salvo com sucesso.');}
 if(view==='admin'){bindForm('exercicio-form',async f=>{const d=formData(f),id=d.id;delete d.id;await api('exercicios',id?'PUT':'POST',d,id?{id}:{});clearExercise();mensagem('Exercício salvo.');await Promise.all([catalogo(),stats()]);});$('cancelar-edicao').addEventListener('click',clearExercise);$('users-prev').addEventListener('click',()=>{usersPage--;users().catch(e=>mensagem(e.message,true));});$('users-next').addEventListener('click',()=>{usersPage++;users().catch(e=>mensagem(e.message,true));});await Promise.all([stats(),users(),catalogo()]);}
}
init().catch(e=>mensagem(e.message,true));
