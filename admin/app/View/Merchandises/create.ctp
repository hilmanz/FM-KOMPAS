<div class="row">
	<a href="<?=$this->Html->url('/merchandises')?>" class="button">Catalog List</a>
</div>
<form action="<?=$this->Html->url('/merchandises/create')?>" method="post" enctype="multipart/form-data">
	<h3>Add Merchandise</h3>
	<table width="100%">
		<tr>
			<td valign="top">
				Name
			</td>
			<td>
				<input type="text" name="name" value=""/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Category
			</td>
			<td>
				<select name="merchandise_category_id">
					<?php foreach($categories as $category):?>
					<option value="<?=$category['MerchandiseCategory']['id']?>">
						<?=h($category['MerchandiseCategory']['name'])?>
					</option>
					<?php endforeach;?>
				</select>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Item Type
			</td>
			<td>
				<select name="merchandise_type">
					<option value="0">
						Non-Digital Item
					</option>
					<option value="1">
						Digital In-Game Item
					</option>
				</select>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Perks
			</td>
			<td>
				<select name="perk_id">
					<option value="0">
						N/A
					</option>
					<?php foreach($perks as $perk):?>
					<option value="<?=$perk['MasterPerk']['id']?>">
						<?=h($perk['MasterPerk']['id'])?> - <?=h($perk['MasterPerk']['perk_name'])?> - <?=h($perk['MasterPerk']['name'])?>
					</option>
					<?php endforeach;?>
				</select>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Description
			</td>
			<td>
				<textarea name='description' cols="100" rows="10"></textarea>
			</td>
		</tr>
		<tr>
			<td valign="top">
				In-game price
			</td>
			<td>
				ss$ <input type="text" name="price_currency" value="0"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				FM Credits Value
			</td>
			<td>
				<input type="text" name="price_credit" value="0"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Real price
			</td>
			<td>
				IDR <input type="text" name="price_money" value="0"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Stock
			</td>
			<td>
				<input type="text" name="stock" value="0"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Picture
			</td>
			<td>
				<input type="file" name="pic"/>
			</td>
		</tr>
		<tr>
			<td valign="top" colspan="2">
				<h3>Create Perk <input type="checkbox" value="1" name="chx"/></h3>
			</td>
			
		</tr>
		<tr>
			<td colspan="2">
				<table width="100%" class="perklist">
					<tr>
						<td>Perk</td>
						<td>Category</td>
						<td>Amount</td>
						<td>Attributes</td>
						<td></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr class="attributes">
			<td>
				Category
			</td>
			<td valign="top">
				<select name="perk_name">
					<option value="ACCESSORIES">ACCESSORIES</option>
					<option value="IMMEDIATE_MONEY">IMMEDIATE MONEY</option>
					<option value="EXTRA_POINTS_PERCENTAGE">EXTRA POINTS PERCENTAGE</option>
					<option value="EXTRA_POINTS_VALUE">EXTRA POINTS VALUE</option>
					<option value="INCOME_MODIFIER">INCOME MODIFIER</option>
					<option value="FREE_PLAYER">FREE PLAYER</option>
					<option value="TRANSFER_DISCOUNT">TRANSFER DISCOUNT</option>
					<option value="POINTS_MODIFIER_PER_CATEGORY">POINTS MODIFIER PER_CATEGORY</option>
				</select>
			</td>
		</tr>
		<tr class="attributes">
			<td valign="top">
				Name
			</td>
			<td>
				<input type="text" name="perk_ident" value=""/>
			</td>
		</tr>
		<tr class="attributes">
			<td valign="top">
				Description
			</td>
			<td>
				<textarea name="perk_description"></textarea>
			</td>
		</tr>
		<tr class="attributes">
			<td valign="top">
				Amount
			</td>
			<td valign="top">
				<input type="text" name="perk_amount" value="1"/>
			</td>
		</tr>
		<tr class="attributes">
			<td valign="top">
				Attributes
			</td>
			<td>
				<div>
				<input type="text" data-id="txt-1" name="attributes[]" value="type" placeholder="name" style="width:300px;" 
				class="txt-attr"/> &nbsp;
				
				<input type="text" data-id="val-1" name="attribute_values[]" value="" placeholder="value" style="width:300px;"/>
				</div>
				<div>
				<input type="text" data-id="txt-2" name="attributes[]" value="category" placeholder="name" style="width:300px;"
				class="txt-attr"/> &nbsp;
				<input type="text" data-id="val-2" name="attribute_values[]" value="" placeholder="value" style="width:300px;"/>
				
				</div>
				<div>
				<input type="text" data-id="txt-3" name="attributes[]" value="point_percentage" placeholder="name" style="width:300px;"
				class="txt-attr"/> &nbsp;
				<input type="text" data-id="val-3" name="attribute_values[]" value="" placeholder="value" style="width:300px;"/>
				</div>
				<div>
				<input type="text" data-id="txt-4" name="attributes[]" value="point_value" placeholder="name" style="width:300px;"
				class="txt-attr"/> &nbsp;
				<input type="text" data-id="val-4" name="attribute_values[]" value="" placeholder="value" style="width:300px;"/>
				</div>
				<div>
				<input type="text" data-id="txt-5" name="attributes[]" value="duration" placeholder="name" style="width:300px;"
				class="txt-attr"/> &nbsp;
				<input type="text" data-id="val-5" name="attribute_values[]" value="" placeholder="value" style="width:300px;"/>
				</div>
				<div>
				<input type="text" data-id="txt-6" name="attributes[]" value="" placeholder="name" style="width:300px;"
				class="txt-attr"/> &nbsp;
				<input type="text" data-id="val-6" name="attribute_values[]" value="" placeholder="value" style="width:300px;"/>
				</div>
				<div>
					<input type="button" name="btn-add-attributes" value="ADD ATTRIBUTES" class="button" />
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="hidden" name="perks" value=""/>
				<input type="submit" name="btn" value="UPLOAD"/>
			</td>
		</tr>
	</table>
</form>
<script id="tplperkrow" type="text/template">
<% for(var i in perks){ %>
	<tr class="perkrows perk-<%=i%>">
		<td><%=perks[i].name%></td>
		<td><%=perks[i].perk_name%></td>
		<td><%=perks[i].amount%></td>
		<td>
		<%
		if(perks[i].attributes != null){
			for(var j in perks[i].attributes){
		%>
			<p><%=j%> : <%=perks[i].attributes[j]%></p>
		<% }}else{%>
			N/A
		<%}%>
		</td>
		<td><a href="#" onclick="removeItem(<%=i%>);return false;">Remove</a></td>
	</tr>
<% } %>
</script>
<script>
var options = {
	'type':[
		'booster','jersey','money','free_player','transfer_window'
	],
	'category':[
		'passing_and_attacking','defending','goalkeeping','mistakes_and_errors'
	]
};

var perks = [];

function removeItem(rowId){
	var new_perks = [];
	for(var i=0; i < perks.length;i++){
		if(i!=rowId){
			new_perks.push(perks[i]);
		}
	}
	$(".perk-"+rowId).remove();
	perks = new_perks;
	updatePerkValues();
}
function updatePerkValues(){
	$("input[name=perks]").val(JSON.stringify(perks));
}
$(document).ready(function(e){
	$(".attributes").hide();
	$(".txt-attr").keyup(function(e){
		addValueField($(this).val(),this);
	});
	$("input[name=chx]").change(function(e){
		console.log($(this).is(':checked'));
		if($(this).is(':checked')){
			$('.attributes').show();
		}else{
			$('.attributes').hide();
		}
	});

	$('.txt-attr').each(function(i,k){
		addValueField($(k).val(),k);
	});

	$("input[name=btn-add-attributes]").click(function(e){
		console.log($("select[name=perk_name]").val());
		console.log($("input[name=perk_ident]").val());
		console.log($("textarea[name=perk_description]").val());
		console.log($("input[name=perk_amount]").val());
		var new_attributes = {};
		$('.txt-attr').each(function(i,k){
			var t = $(k).attr('data-id').split('-');
			var id = t[1];
			if($('.txt-attr-val-'+id).val()!=""){
				new_attributes[$(k).val()] = $('.txt-attr-val-'+id).val();	
			}
			
		});
		perks.push({
				perk_name:$("select[name=perk_name]").val(),
				name:$("input[name=perk_ident]").val(),
				description:$("textarea[name=perk_description]").val(),
				amount:$("input[name=perk_amount]").val(),
				attributes:new_attributes
			});

		console.log(perks);
		displayPerks(perks);
		updatePerkValues();
	});
});
function displayPerks(perks){
	$(".perkrows").remove();
	append_view(tplperkrow,'.perklist',{perks:perks});
}
function addValueField(name,obj){
	console.log(name);
	$(obj).next().remove();

	var t = $(obj).attr('data-id').split('-');

	if(typeof options[name] !== 'undefined'){
		var el = options[name];
		var str = "<select name='attribute_values[]' data-id='val-"+t[1]+"' class='txt-attr-val-"+t[1]+"'>";
		for(var i in el){
			str += "<option value='"+el[i]+"'>"+el[i]+"</option>";
		}
		str += "</select>";
		$(obj).after("&nbsp;"+str);
	}else{
		$(obj).after('&nbsp;<input type="text" name="attribute_values[]" data-id="val-'+t[1]+'" value="" placeholder="value" style="width:300px;" class="txt-attr-val-'+t[1]+'"/>');
	}
}
</script>