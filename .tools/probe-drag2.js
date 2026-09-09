(function(){
const $=window.jQuery;
const say=(m,d)=>console.log('%c'+m,'color:#06c;font-weight:bold',d===undefined?'':d);

// Run BEFORE layout_editor's own kform_field_added handler by moving ours to
// the front of jQuery's handler list. What it sees is what that handler sees.
$(document).on('kform_field_added.kdnaprobe2',function(e,form,field){
  const ind=$('#indicator');
  const t=ind.data('target');
  say('AT HANDLER ENTRY for field '+field.id,{
    indicatorCount: ind.length,
    indicatorHasTargetData: t!==undefined,
    target: t&&t.length?(t[0].id||'(no id)'):String(t),
    targetStillInDom: t&&t.length?document.contains(t[0]):'n/a',
    where: ind.data('where')||'NONE',
    allIndicatorData: JSON.stringify(Object.keys(ind.data()||{})),
    newFieldExists: $('#field_'+field.id).length,
    newFieldIndex: $('#kform_fields').children().index($('#field_'+field.id))
  });
  // moveByTarget resolves the target through its group. An unset or unmatched
  // group id makes that set empty, and inserting relative to an empty set is a
  // silent no-op -- which looks exactly like the handler never running.
  if(t&&t.length){
    const gid=$(t[0]).attr('data-groupId');
    say('  target group',{
      targetGroupId: gid===undefined?'ATTRIBUTE NOT SET':gid,
      membersOfThatGroup: gid===undefined?0:$('#kform_fields').find('.kfield').filter('[data-groupId="'+gid+'"]').length,
      newFieldGroupId: $('#field_'+field.id).attr('data-groupId')||'(none)'
    });
  }
});
const ev=$._data(document,'events');
if(ev&&ev.kform_field_added&&ev.kform_field_added.length>1){
  ev.kform_field_added.unshift(ev.kform_field_added.pop());
  say('probe moved to front of '+ev.kform_field_added.length+' handlers','ok');
}else{say('WARNING: could not reorder handlers','probe may run second');}

// Catch the move itself. If moveByTarget acts, it calls one of these on the
// new field; if it returns early, neither fires.
['insertBefore','insertAfter'].forEach(function(fn){
  const orig=$.fn[fn];
  $.fn[fn]=function(target){
    const subject=this[0];
    if(subject&&subject.id&&/^field_\d+$/.test(subject.id)){
      const $t=$(target);
      say('  '+fn+'('+(($t[0]&&$t[0].id)||'EMPTY SET, length '+$t.length)+') on '+subject.id,
          $t.length?'':'<- inserting relative to nothing moves nothing');
    }
    return orig.apply(this,arguments);
  };
});
say('insertBefore/insertAfter wrapped','ok');

say('drag a field into the middle of the form now');
})();
