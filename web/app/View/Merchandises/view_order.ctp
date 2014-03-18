
<div id="catalogPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
    <div id="thecontent">
        <div class="content">
        	<div class="titlePage">
				<h1 class="red">
					Online Catalog
				</h1>
				<h4>Detil Pemesanan
				</h4>
            </div>
            <div class="rowd">
				<div class="col-content">
					<div class="tr widget">
						<h1>Kode Transaksi : <?=h($rs['MerchandiseOrder']['po_number'])?></h1>
						
					</div>
					<div class="tr widget">
						<?php
							$shopping_cart = unserialize($rs['MerchandiseOrder']['data']);
						?>
		                <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF">
		                    <tr>
		                        <td></td>
		                        <td>Item</td>
		                        <td>Harga Satuan</td>
		                        <td>Jml</td>
		                        <td>Total</td>
		                      
		                    </tr>
							<?php
							$total_price = 0;
		                    $total_coins = 0;
							for($i=0;$i<sizeof($shopping_cart);$i++):
		                        $item = $shopping_cart[$i]['data']['MerchandiseItem'];
		                    	$coins = 0;
		                    	$price = 0;
		                    	$pic = Configure::read('avatar_web_url').
										"merchandise/thumbs/0_".
										$item['pic'];
		                    	
		                    ?>
		                    <tr class="tr-<?=intval($item['id'])?>">
		                        <td>
		                        	<img src="<?=$pic?>" width="100px"/>
		                        </td>
		                        <td> #<?=h($item['id'])?> -
		                            <?=h($item['name'])?>
		                        </td>
		                        <td>
		                           
		                            <p class="price">   
		                                Rp. <?=number_format(intval($item['price_money']))?> 
		                            </p> 
		                           
		                            
		                        </td>
		                        <td>
		                            <?=(intval($shopping_cart[$i]['qty']))?>
		                            <?php
		                            	
		                            	$price = intval($shopping_cart[$i]['qty']) * 
		                            						intval($item['price_money']);

		                            	$total_price += $price;
		                            ?>
		                        </td>
		                        <td>
		                            Rp. <span class="prices price-<?=$item['id']?>">
		                                <?=number_format(intval($price))?>
		                            </span>
		                           
		                           
		                        </td>
		                        
		                    </tr>
		                   
		                    <?php endfor;?>
		                    <tr>
		                        <td></td>
		                        <td>Ongkos Kirim</td>
		                        <td></td>
		                        <td></td>
		                        <td>
		                        	<?php
		                    		$ongkos = 0;
		                    		foreach($ongkir as $cost){

		                    			if($cost['Ongkir']['id']==$city_id){
		                    				$ongkos = $cost['Ongkir']['cost'];
		                    				break;
		                    			}
		                    		}
		                    		?>
		                        	Rp. <?=number_format($ongkos)?>
		                        </td>
		                      
		                    </tr>
		                    <tr>
		                        <td></td>
		                        <td>Administrative &amp; Handling Fee</td>
		                        <td></td>
		                        <td></td>
		                        <td>
		                        	<?php
		                        	$admin_fee = 0;
		                        	if($rs['MerchandiseOrder']['payment_method']=='ecash'){
		                        		$admin_fee = Configure::read('PO_ADMIN_FEE');
		                        	}
		                        	?>
		                        	<?php if($admin_fee==0):?>
		                        	GRATIS
		                        	<?php else:?>
		                        	Rp. <?=number_format($admin_fee)?>
		                        	<?php endif;?>
		                        </td>
		                      
		                    </tr>
		                    <tr>
		                    	<td></td>
		                        <td colspan="3" align="right">Belanja Total</td>
		                        <td>
		                            <span class="total-price">
		                            	
		                            	
		                            		Rp. <?=number_format($total_price+$admin_fee+$ongkos)?>
		                                   
		                               
		                            </span>
		                        </td>
		                    </tr>
		                </table>
 
					</div><!-- end .widget -->
					<div class="tr widget">
						<p>
							<a class="button" href="<?=$this->Html->url('/merchandises/history')?>">
								Kembali ke Daftar Transaksi
							</a>
						</p>
					</div><!-- end .widget -->
				</div><!-- end .col-content -->
				
           
				<div class="box4 fr">
                    <?php for($i=0;$i<sizeof($sidebar_banner);$i++):?>
			        	<div class="banner300x250">
						     <a href="javascript:banner_click(<?=$sidebar_banner[$i]['Banners']['id']?>,'<?=$sidebar_banner[$i]['Banners']['url']?>');" target="_blank">
			                    <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
			                                $sidebar_banner[$i]['Banners']['banner_file'])?>" />
			                </a>
			            </div>
		            <?php endfor;?>
				</div><!-- end .box4 -->
            </div><!-- end .row-3 -->
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #catalogPage -->

<script>
var browse_url = "<?=$browse_url?>";
$("select[name=cid]").change(function(e){
	document.location = browse_url+''+parseInt($(this).val());
});
</script>