<div id="fillDetailsPage">
     <?php echo $this->element('infobar'); ?>
    <div id="thecontent">
        <div id="content">
            <div class="content">
                <div class="row-2">
                    <h1 class="red">Perekrutan Staff</h1>
                    <p>Tentukan sendiri staff mana yang akan Anda rekrut untuk membantu Anda mengelola tim dan klab secara maksimal. Pilih dengan bijak dan sesuaikan dengan kondisi keuangan.</p>
                </div><!-- end .row-2 -->
                <form class="theForm">
                    <div class="row-2">
                        <div class=" staff-list" id="available">
                            <?php
                                foreach($officials as $official):
                                    $img = str_replace(' ','_',strtolower($official['name'])).'.jpg';
                            ?>
                            <div class="thumbStaff">
                                <div class="avatar-big">
                                    <img src="<?=$this->Html->url('/content/thumb/'.$img)?>" />
                                </div><!-- end .avatar-big -->
                                <p><?=h($official['name'])?></p>
                                <div>
                                    SS$ <?=number_format($official['salary'])?> / minggu
                                </div>
                                <div>
                                    <?php if(@$official['hired']):?>
                                        <a href="?dismiss=1&id=<?=$official['id']?>" class="button">Berhentikan</a>
                                    <?php else:?>
                                        <a href="?hire=1&id=<?=$official['id']?>" class="button">Rekrut</a>
                                    <?php endif;?>
                                </div>
                            </div><!-- end .thumbStaff -->
                            <?php
                                endforeach;
                            ?>
                        </div><!-- end .col2 -->
                      
                    </div><!-- end .row-2 -->
                   
                </form>
            </div><!-- end .content -->
        </div><!-- end #content -->
    <div id="sidebar" class="tr">
        <div class="widget">
            <div class="cash-left"
                <h3 class="red">SISA UANG</h3>
                <h1>SS$ <?=number_format($team_bugdet)?></h1>
                <h3 class="red">Est. PENGELUARAN MINGGUAN</h3>
                <h1>SS$ <?=number_format($weekly_salaries*4)?></h1> 
            </div>
        </div><!-- end .widget -->
    </div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->