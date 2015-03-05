jQuery("form#searchydoo input.taxonomy").click(function() {
  searchydoo_hide_subcategories();
  searchydoo_show_subcategories();
  searchydoo_mark_post_type()
});

jQuery(document).ready(function() {
  
  jQuery('.tax-select select').attr('disabled', true);
  parseQuery(captureSearchString());  
  setQueryValues();
  //searchydoo_show_subcategories();  
  searchydoo_expose_filter();
  //searchydoo_mark_post_type();
  
});

jQuery('form#searchydoo input.term').click(function() {
  jQuery("form#searchydoo .post_type").val('');
});

function searchydoo_hide_subcategories() {
  jQuery("form#searchydoo blockquote.content-type").css("display", "none");
  jQuery("form#searchydoo blockquote.content-type input.medium").attr("disabled", "disabled");
}

function searchydoo_show_subcategories() {
  jQuery("form#searchydoo .taxonomy:checked").each(function() {
    var this_id = jQuery(this).attr('id');
    jQuery("form#searchydoo blockquote.content-type."+this_id).css("display", "");
    jQuery("form#searchydoo blockquote.content-type."+this_id+" input").removeAttr("disabled");
  });
}

function searchydoo_mark_post_type() {
  var any_terms = false;
  jQuery("form#searchydoo .term:checked").each(function() {
    any_terms = true;
  });
  if(!any_terms) {
    jQuery("form#searchydoo .taxonomy:checked").each(function() {
      jQuery("form#searchydoo .post_type").val(jQuery(this).val());
    });
  }
}

function searchydoo_expose_filter(){
   jQuery('#post_type_select select').change(function(){
     var content_type = jQuery('#post_type_select option:selected').val();
    
    jQuery('.tax-select').removeClass('active');
    jQuery('.tax-select select').attr('disabled',true);
    
    switch(content_type){
      case 'post': jQuery('#tax-select-category').addClass('active');
                   jQuery('#tax-select-category select').attr('disabled',false);
        break;
      case 'book': jQuery('#tax-select-book-types').addClass('active');
                   jQuery('#tax-select-book-types select').attr('disabled',false); 
        break;
      case 'video' : jQuery('#tax-select-video-type').addClass('active');
                     jQuery('#tax-select-video-type select').attr('disabled',false);
        break;
      default :
      break;
    }
  
   }
   );  
}

function captureSearchString(){
  $search_string = document.location.search;  
  return $search_string;
}

function parseQuery(str){
  if(typeof str != "string" || str.length == 0) return {};
  var s = str.split("&");
  var s_length = s.length;
  var bit, query = {}, first, second;
  for(var i = 0; i < s_length; i++)
      {
      bit = s[i].split("=");
      first = decodeURIComponent(bit[0]);
      if(first.length == 0) continue;
      second = decodeURIComponent(bit[1]);
      if(typeof query[first] == "undefined") query[first] = second;
      else if(query[first] instanceof Array) query[first].push(second);
      else query[first] = [query[first], second]; 
      }
  return query;
}

function setQueryValues(){
  $collection = parseQuery(captureSearchString());
  
  jQuery.each($collection, function(k,v){
     if(k=="?s"){
        return;
      }
    //console.log(k+ ' is ' +v);
    jQuery('select[name='+k+'] option[value='+v+']').prop('selected',true);
    
     
     
     if((k=="video-type")||(k=="category")||(k=="book-types")){
      $unhide_me = jQuery('#tax-select-'+k).addClass('active');
      //console.log($unhide_me);
      jQuery('#tax-select-'+k+' select').removeProp('disabled');
      jQuery('#tax-select-'+k+' option[value='+v+']').prop('selected',true);
     }
  });
}
