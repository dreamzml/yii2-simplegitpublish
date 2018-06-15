<?php
/**
 * @Author: 16020028
 * @Date:   2017-05-16 14:57:45
 * @Last Modified by:   16020028
 * @Last Modified time: 2017-05-16 19:15:16
 */

use yii\helpers\Html;

?>

<div class="row">
    <div class="col-md-2">
        <label class="pull-left">合并测试的分支</label>
    </div>
    <div class="col-md-10">
        <ul>
            <?php foreach ($mergeBranchs as $branch): ?>
                <?php if(strpos($branch, '---[separator]---')===false){ ?>
                    <li class=""><?php if(YII_ENV_TEST && $isMaster) echo Html::a('编译合并到生产分支', 'JavaScript:;', ['branch'=>$branch, 'class'=>'btn badge btn-link push-btn btn-sm']) ?><?= $branch ?></li>
                <?php }else{ ?>
                    <?php list($oriBranch, $subBranch) = explode($branch, '---[separator]---');  ?>
                    <li class=""><?php if(YII_ENV_TEST && $isMaster) echo Html::a('编译合并到生产分支', 'JavaScript:;', ['branch'=>$oriBranch, 'subBranch'=>$subBranch, 'class'=>'btn badge btn-link push-btn btn-sm']) ?><?= "$oriBranch  ------ $subBranch" ?></li>
                <?php } ?>
          <?php endforeach ?>
        </ul>
    </div>
</div>