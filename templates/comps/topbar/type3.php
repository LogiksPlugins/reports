<div class="control-primebar tools_type3">
	<div class="col-lg-6 col-xs-6 pull-left control-toolbar">
        <div class="actionBlock dropdown">
            <button class="btn  dropdown-toggle" type="button" data-toggle="dropdown"><i class="ti ti-player-play"></i>
            <span class="caret"></span></button>
            <ul class="dropdown-menu">
              <li><a href="#">HTML</a></li>
              <li><a href="#">CSS</a></li>
              <li><a href="#">JavaScript</a></li>
            </ul>
        </div>
        <div class="SearchBarmain" >
            <div class="tags-input reportSearchBar" id="myTags">
                    <span class="data">
                        <span class="tag"><span class="text" _value="Nairobi 047">jQuery</span><span class="close">&times;</span></span>
                        <span class="tag"><span class="text" _value="24">Script</span><span class="close">&times;</span></span>
                    </span>
                    <span class="autocomplete">
                        <input type="text">
                        <div class="autocomplete-items">
                            
                        </div>
                    </span>
                <i class="ti ti-search serachIcon"></i>
            </div>
            <div class="dropdown filterBox">
                <label class=" dropdown-toggle" data-toggle="dropdown"><i class="ti ti-filter"></i> Filter</label>
                
                <ul class="dropdown-menu">
                  <li><a href="#">HTML</a></li>
                  <li><a href="#">CSS</a></li>
                  <li><a href="#">JavaScript</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xs-6 pull-right uitype_type1 " style="">
        <div class="input-group pull-right" style="text-align: right;">
                <div class="uiswitcher">
                    <div class="btn-group">
                        <button type="button" cmd="ui@grid" class="btn btn-default btn-active" title="Grid Table">
                           <i class="ti ti-layout-grid-remove"></i>
                        </button>
                        <button type="button" cmd="ui@kanban" class="btn btn-default" title="Kanban">
                                <i class="ti ti-list"></i>
                        </button>                
                    </div>
                </div>
                <div class="paginationBlock">
                    <citie class="displayCounter"> <span class="recordsIndex">1</span>-<span class="recordsUpto">20</span> / <span class="recordsMax">1093</span> </citie>
                    <div class="pagenationSlider" role="group" aria-label="pagination">
	                  <button type="button" class="btn btn-default" cmd="prevPage"><i class="ti ti-chevron-left"></i></button>
	                  <select class="perPageCounter autorefreshReport" name="limit">
	            	    <option>5</option><option>10</option><option selected="">20</option><option>50</option><option>100</option><option>500</option><option>1000</option><option>5000</option>			            </select>
	                  <button type="button" class="btn btn-default" cmd="nextPage"><i class="ti ti-chevron-right"></i></button>
	                </div>
                </div>
        </div>
    </div>
</div>
<script>
$(function () {
    $("body").delegate(".subfilter", "click",function(){
       $(".reportFilter").toggleClass("open");
    });
    //console.log($('#myTags').tagsValues())
})

function runSuggestions(element,query) {
    let sug_area=$(element).parents().eq(2).find('.autocomplete .autocomplete-items');
    $.getJSON("data.json", function( data ) {
        _tag_input_suggestions_data = data;
        $.each(data,function (key,value) {
            let template = $("<div>"+value.name+"</div>").hide()
            sug_area.append(template)
            template.show()

        })
    });
}     
</script>