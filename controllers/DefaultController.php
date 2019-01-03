<?php

namespace dreamzml\SimpleGitPublish\controllers;

use Yii;
use yii\web\Controller;

/**
 * Default controller for the `simplegitpublish` module
 */
class DefaultController extends Controller
{
    public $layout = 'simple';
    //管理员
    public $isMaster = false;

    //主分支仓库
    public $masterRemote = 'awsGitlab';
    public $masterBranch = 'yimi_wechatmall';

    //跳出CsrfToken验证
    public $enableCsrfValidation = false;
    
    public function beforeAction($action) : bool {

        Yii::$app->cache->keyPrefix .= '_'.Yii::$app->name;

        $res = parent::beforeAction($action);
        if ($res === false) {
            return false;
        }

        $this->isMaster = $this->module->monitors=='*' || $_SERVER['PHP_AUTH_USER'] == "MASTER";
        $this->masterRemote = $this->module->masterRemote;
        $this->masterBranch = $this->module->masterBranch;
        return true;
    }
    
    /**
     * 引导页
     * @return [type] [description]
     */
    public function actionIndex() {
        $gitRoot = self::getGitBootPath();

        //获取所有分支
        $remoteBranch = $this->getAllBranchByPath($gitRoot);

        //获取子项目所有分支
        $subRemoteBranch = empty($this->module->subGitPath)? [] : $this->getAllBranchByPath($gitRoot.$this->module->subGitPath);

        //获取当前分支
        $currentBranch = $this->getCurrentBranch();
        $currentSubBranch = empty($this->module->subGitPath)? '' : $this->getCurrentSubBranch();

        return $this->render('index', [
            'remoteBranch'  => $remoteBranch,
            'currentBranch' => $currentBranch,
            'currentSubBranch' => $currentSubBranch,
            'masterRemote' => $this->masterRemote,
            'masterBranch' => $this->masterBranch,
            'subGitPath' => $this->module->subGitPath,
            'subRemoteBranch'  => $subRemoteBranch,
            'subMasterRemote' => $this->module->subMasterRemote,
            'subMasterBranch' => $this->module->subMasterBranch,
        ]);
    }
    
    /**
     * 更新git
     * @return [type] [description]
     */
    public function actionGitStatus() {
        
        //如果连接断开，继续执行
        ignore_user_abort(true);
        set_time_limit(0);
        
        //git项目地址
        $gitRoot = self::getGitBootPath();
        
        $shell = "cd $gitRoot && git status 2>&1";
        
        $strout = "<span class='text-warning'># {$shell}</span> \n";
        $strout .= shell_exec($shell);


        if(!empty($this->module->subGitPath)){
            $shell = "cd $gitRoot{$this->module->subGitPath} && git status 2>&1";
            $strout .= "\n<span class='text-warning'># {$shell}</span> \n";
            $strout .= shell_exec($shell);
        }

        return "<pre>$strout</pre>";
    }
    
    /**
     * 更新git
     * 默认更新主分支
     * @return [type] [description]
     */
    public function actionSync() {
        
        //如果连接断开，继续执行
        ignore_user_abort(true);
        set_time_limit(0);
        
        //写入日志
        yii::info('git pull run star', 'githook');
        
        //git项目地址
        $gitRoot = self::getGitBootPath();
        
        $oriBranch = Yii::$app->request->get('branch', "{$this->masterRemote}/{$this->masterBranch}");
        $subBranch = Yii::$app->request->get('subBranch', "{$this->module->subMasterRemote}/{$this->module->subMasterBranch}");

        //验证分支
        if(!in_array($oriBranch, $this->getAllBranchByPath($gitRoot))){
            $strout = "\n\n<span class='text-warning'>***************************************************************\n***************     分支不存在或不正确       *****************\n***************************************************************\n</span>";
            return "<pre>$strout</pre>";
        }
        if(!empty($this->module->subGitPath) && !in_array($subBranch, $this->getAllBranchByPath($gitRoot.$this->module->subGitPath))){
            $strout = "\n\n<span class='text-warning'>***************************************************************\n***************        子项目分支不存       *****************\n***************************************************************\n</span>";
            return "<pre>$strout</pre>";
        }

        //合并分支
        $branch    = str_replace('/', ' ', $oriBranch);
        $shell     = "cd $gitRoot && git pull {$branch} 2>&1";
        $strout    = "<span class='text-warning'># {$shell}</span> \n";
        $outPutCmd = shell_exec($shell);
        $strout    .= $outPutCmd;

        //子项目合并分支
        if(!empty($this->module->subGitPath)){
            $branch    = str_replace('/', ' ', $subBranch);
            $shell     = "cd $gitRoot{$this->module->subGitPath} && git pull {$branch} 2>&1";
            $strout    .= "<span class='text-warning'># {$shell}</span> \n";
            $outPutCmd = shell_exec($shell);
            $strout    .= $outPutCmd;
        }

        //获取当前分支
        $currentBranch =  $this->getCurrentBranch();
        $currentSubBranch = empty($this->module->subGitPath)? '' : $this->getCurrentSubBranch();

        $mergeBranchs = Yii::$app->cache->get('merge_branchs');
        if (!YII_ENV_PROD) {

            //合并有文件冲突
            if (strpos($outPutCmd, 'Automatic merge failed;') !== false) {
                Yii::$app->response->setStatusCode(424);
                
                //重置当前分支
                $strout     .= "\n\n<span class='text-warning'>***************************************************************\n******     合并失败， 文件冲突，请解决冲突后再合并测试       ******\n***************************************************************\n</span>";

                $strout    .= "\n\n<span class='text-warning'># 回滚分支</span> \n";

                $branchName = explode('/', $currentBranch);
                $shell      = "cd $gitRoot && git reset --hard {$currentBranch} &&  git pull {$branchName[0]} {$branchName[1]} 2>&1";
                $strout    .= "<span class='text-warning'># {$shell}</span> \n";
                $outPutCmd = shell_exec($shell);
                $strout    .= $outPutCmd;

                //重置当前子分支
                $branchName = explode('/', $currentSubBranch);
                $shell = "cd $gitRoot{$this->module->subGitPath} && git reset --hard {$currentSubBranch} && git pull {$branchName[0]} {$branchName[1]} 2>&1";
                $strout    .= "<span class='text-warning'># {$shell}</span> \n";
                $outPutCmd = shell_exec($shell);
                $strout    .= $outPutCmd;

                //合并其它待测分支
                $mergeBranchs = is_array($mergeBranchs)? $mergeBranchs : [];
                foreach ((array)$mergeBranchs as $key => $mBranch) {
                    list($mBranch,$mSubBranch) = explode('---[separator]---', $mBranch);

                    if($currentBranch != $mBranch) {
                        $mBranch = str_replace('/', ' ', $mBranch);
                        $shell = "cd $gitRoot && git pull {$mBranch} 2>&1";
                        $strout    .= "<span class='text-warning'># {$shell}</span> \n";
                        $outPutCmd = shell_exec($shell);
                        $strout    .= $outPutCmd;
                    }

                    if($currentSubBranch != $mSubBranch) {
                        $mSubBranch = str_replace('/', ' ', $mSubBranch);
                        $shell = "cd $gitRoot && git pull {$mSubBranch} 2>&1";
                        $strout    .= "<span class='text-warning'># {$shell}</span> \n";
                        $outPutCmd = shell_exec($shell);
                        $strout    .= $outPutCmd;
                    }
                }
            } elseif ( ($currentBranch != $oriBranch && $oriBranch != $this->masterRemote.'/'.$this->masterBranch && !empty($oriBranch))
                || (!empty($currentSubBranch) && $subBranch != $currentSubBranch && $subBranch != $oriBranch)) {
                $mergeBranchs[] = empty($this->module->subGitPath)? $oriBranch :  "{$oriBranch}---[separator]---{$subBranch}";
                $mergeBranchs   = array_unique($mergeBranchs);
            }

            $mergeBranchs && sort($mergeBranchs);

            Yii::$app->cache->set('merge_branchs', $mergeBranchs);
        }
        
        if (function_exists('opcache_reset'))
            opcache_reset();
        
        return "<pre>$strout</pre>";
    }
    
    /**
     * 更新git
     * @return [type] [description]
     */
    public function actionReset() {
        
        //如果连接断开，继续执行
        ignore_user_abort(true);
        set_time_limit(0);
        
        //写入日志
        yii::info('git pull run star', 'githook');
        
        //git项目地址
        $gitRoot = self::getGitBootPath();


        $branch = Yii::$app->request->get('branch');
        $subBranch = Yii::$app->request->get('subBranch');

        //验证分支
        if(!in_array($branch, $this->getAllBranchByPath($gitRoot))){
            $strout = "\n\n<span class='text-warning'>***************************************************************\n***************     分支不存在或不正确       *****************\n***************************************************************\n</span>";
            return "<pre>$strout</pre>";
        }
        if(!empty($this->module->subGitPath) && !in_array($subBranch, $this->getAllBranchByPath($gitRoot.$this->module->subGitPath))){
            $strout = "\n\n<span class='text-warning'>***************************************************************\n***************        子项目分支不存       *****************\n***************************************************************\n</span>";
            return "<pre>$strout</pre>";
        }


        Yii::$app->cache->delete('merge_branchs');

        //获取当前分支
        $currentBranch = $this->getCurrentBranch();
        $branchName = explode('/', $branch);
        $realBranch = $branchName[1];
        if($currentBranch == $branch){
            //重置当前分支
            $shell = "cd $gitRoot && git reset --hard {$branch} && git pull {$branchName[0]} {$branchName[1]} 2>&1";
        }else{
            //设置当前的remote
            Yii::$app->cache->set('currentMasterRemote', $branchName[0]);
            //删除已存在的本地分支
            shell_exec(" cd $gitRoot &&  git branch -D {$realBranch} 2>&1");
            $shell = "cd $gitRoot && git fetch --all && git checkout -b {$realBranch} {$branch} && git pull {$branchName[0]} {$branchName[1]} 2>&1";
        }

        $strout = "<span class='text-warning'># {$shell}</span> \n";
        $strout .= shell_exec($shell);

        if(!empty($this->module->subGitPath)) {
            $currentSubBranch = $this->getCurrentSubBranch();
            $branchName = explode('/', $subBranch);
            $realBranch = $branchName[1];
            if ($currentSubBranch == $subBranch) {
                //重置当前分支
                $shell = "cd $gitRoot{$this->module->subGitPath} && git reset --hard {$branch} && git pull {$branchName[0]} {$branchName[1]} 2>&1";
            } else {
                //设置当前的remote
                Yii::$app->cache->set('currentMasterRemote', $branchName[0]);
                //删除已存在的本地分支
                shell_exec(" cd $gitRoot{$this->module->subGitPath} &&  git branch -D {$realBranch} 2>&1");
                $shell = "cd $gitRoot{$this->module->subGitPath} && git fetch --all && git checkout -b {$realBranch} {$subBranch} && git pull {$branchName[0]} {$branchName[1]} 2>&1";
            }
            $strout .= "<span class='text-warning'># {$shell}</span> \n";
            $strout .= shell_exec($shell);
        }


        return "<pre>$strout</pre>";
    }
    
    /**
     * 编译js
     * @return [type] [description]
     */
    public function actionWebpack() {
        $force = Yii::$app->request->get('force');
        $webpackEvn = Yii::$app->request->get('evn', 'test');
        
        //如果连接断开，继续执行
        ignore_user_abort(true);
        set_time_limit(0);
        
        //如果无jsx改动跳过
        if (!$this->modifieldIncloudJsx() && $force != 'true' || empty($this->module->compilePath)) {
            return "<pre><span class='text-warning'># </span><span class='text-muted'>modified file not incloud jsx, skip webpack</span> \n</pre>";
        }
        
        //写入日志
        yii::info('webpack run star', 'githook');
        
        //git项目地址
        $gitRoot = self::getGitBootPath();
        $wwwRoot = "$gitRoot{$this->module->nodeBasePath}";

        $compileWebpackCmd = $webpackEvn=='test'? $this->module->compileWebpackCmdTest: $this->module->compileWebpackCmdProd;
        
        $shell = "cd $wwwRoot && rm -rf $gitRoot{$this->module->compilePath}/* && {$compileWebpackCmd} 2>&1";
        
        $strout = "<span class='text-warning'># {$shell}</span> \n";
        $strout .= shell_exec($shell);
        
        return "<pre>$strout</pre>";
    }
    
    /**
     * 压缩js
     * @return [type] [description]
     */
    public function actionGulp() {
        $force = Yii::$app->request->get('force');
        //如果连接断开，继续执行
        ignore_user_abort(true);
        set_time_limit(0);
        
        //如果无jsx改动跳过
        if (!$this->modifieldIncloudJsx() && $force != 'true' || empty($this->module->compilePath) || empty($this->module->compileGulpCmd)) {
            return "<pre><span class='text-warning'># </span><span class='text-muted'>modified file not incloud jsx, skip guip</span> \n</pre>";
        }
        
        //写入日志
        yii::info('gulp run star', 'githook');
        
        //git项目地址
        $gitRoot = self::getGitBootPath();
        $wwwRoot = "$gitRoot{$this->module->nodeBasePath}";
        
        $shell = "cd $wwwRoot && {$this->module->compileGulpCmd} 2>&1";
        
        $strout = "<span class='text-warning'># {$shell}</span> \n";
        $strout .= shell_exec($shell);
        
        return "<pre>$strout</pre>";
    }
    
    /**
     * 上传压缩文件
     * @return [type] [description]
     */
    public function actionGulpPush() {
        //如果连接断开，继续执行
        ignore_user_abort(true);
        set_time_limit(0);
        
        $branch = Yii::$app->request->get('branch');
        $branch = str_replace('/', ' ', $branch);

        $subBranch = Yii::$app->request->get('subBranch');
        $subBranch = str_replace('/', ' ', $subBranch);

        //写入日志
        yii::info("git push branch {$branch} run star", 'githook');

        //git项目地址
        $gitRoot = self::getGitBootPath();
        $strout = "<span class='text-warning'>### push cmd ###</span> \n\n";

        $shell = "cd $gitRoot && git status 2>&1";
        $status = shell_exec($shell);
        $changes = strpos($status, "nothing to commit") === false;
        //$strout .= "<span class='text-warning'># {$shell}</span> \n";
        //$strout .= $status;

        if(!empty($this->module->subGitPath)) {
            $shell   = "cd $gitRoot{$this->module->subGitPath} && git status 2>&1";
            $status  = shell_exec($shell);
            $changes = $changes || (strpos($status, "nothing to commit") === false);
            //$strout  .= "\n<span class='text-warning'># {$shell}</span> \n";
            //$strout  .= $status;
        }

        //git 添加编译生成的文件
        if($changes && !empty($this->module->compilePath)){
            $addCmd = " cd $gitRoot{$this->module->compilePath} && git add --all * && git commit -am \"update gulp js file\" 2>&1 ";
            $strout .= "\n<span class='text-warning'># {$addCmd}</span> \n";
            $strout .= shell_exec($addCmd);
        }

        $shell = "cd $gitRoot && git push $branch 2>&1";
        $strout .= "\n<span class='text-warning'># {$shell}</span> \n";
        $strout .= shell_exec($shell);

        if(!empty($this->module->subGitPath)) {
            $shell = "cd $gitRoot{$this->module->subGitPath} && git push $subBranch 2>&1";
            $strout .= "\n<span class='text-warning'># {$shell}</span> \n";
            $strout .= shell_exec($shell);
        }
        
        return "<pre>$strout</pre>";
    }
    
    /**
     * 清空缓存
     * @return [type] [description]
     */
    public function actionFlushCache() {
        $res = Yii::$app->cache->flush();
        if ($res) {
            echo "操作成功";
        } else {
            echo "清空缓存失败";
        }
    }
    
    /**
     * git根目录
     * @return [type] [description]
     */
    public static function getGitBootPath() {
        $baseDir = Yii::$app->getBasePath();

        if(is_dir($baseDir.'/.git')){
            $gitRoot = $baseDir;
        }else{
            $gitRoot = dirname($baseDir);
        }

        return $gitRoot;
    }

    /**
     * 获取当前分支
     * @return [type] [description]
     */
    public function getCurrentBranch() {
        $gitRoot = $this->getGitBootPath();

        $masterRemote = Yii::$app->cache->get('currentMasterRemote');
        $masterRemote = empty($masterRemote)? $this->masterRemote : $masterRemote;

        return  $masterRemote.'/'.trim(shell_exec(" cd $gitRoot && git symbolic-ref --short -q HEAD 2>&1"));
    }

    /**
     * 获取当前分支
     * @return [type] [description]
     */
    public function getCurrentSubBranch() {
        $gitRoot = $this->getGitBootPath();

        $masterRemote = Yii::$app->cache->get('currentSubMasterRemote');
        $masterRemote = empty($masterRemote)? $this->module->subMasterRemote : $masterRemote;

        return  $masterRemote.'/'.trim(shell_exec(" cd $gitRoot{$this->module->subGitPath} && git symbolic-ref --short -q HEAD 2>&1"));
    }

    /**
     * 判断未commit的文件中是否有jsx文件改动
     * @return [type] [description]
     */
    public function modifieldIncloudJsx() {
        //git项目地址
        $gitRoot = self::getGitBootPath();

        //判断node是否在主项目
        if(strpos($this->module->nodeBasePath,$this->module->subGitPath)===false){
            //node在子git项目
            $masterBranch = $this->module->subMasterRemote.'/'.$this->module->subMasterBranch;
        }else{
            //node在主git项目
            $masterBranch = $this->masterRemote.'/'.$this->masterBranch;
        }

        $commitIds = shell_exec("cd $gitRoot{$this->module->nodeBasePath} && git cherry -v $masterBranch 2>&1");
        $commitIds = explode("\n", rtrim($commitIds));
        foreach ($commitIds as $commit) {
            $commit = explode(' ', $commit);
            if (isset($commit[1])) {
                $status = shell_exec("cd $gitRoot{$this->module->nodeBasePath} && git diff-tree --no-commit-id --name-only -r {$commit[1]} 2>&1");
                if (!(stripos($status, '.js') === false) || !(stripos($status, '.vue') === false))
                    return true;
            }
        }
        
        return false;
    }
    
    /**
     * 合并测试的分支
     * @return [type] [description]
     */
    public function actionUnionBranch() {
        
        $mergeBranchs = Yii::$app->cache->get('merge_branchs');
        if (!$mergeBranchs) {
            return null;
        }
        
        return $this->renderPartial('_union_branch', [
            'mergeBranchs' => $mergeBranchs,
            'isMaster'     => $this->isMaster,
        ]);
    }
    
    /**
     * 合并测试的分支
     * @return [type] [description]
     */
    public function actionDeleteUnionBranch() {
        $branch = Yii::$app->request->get('branch');
        
        $mergeBranchs = Yii::$app->cache->get('merge_branchs');
        if (!$branch || !$mergeBranchs) {
            return null;
        }
        
        unset($mergeBranchs[array_search($branch, $mergeBranchs)]);
        Yii::$app->cache->set('merge_branchs', $mergeBranchs);
    }

    /**
     * 获取目录下的git 项目后有分支
     * @param string $path
     */
    private function getAllBranchByPath(string $path) {
        shell_exec(" cd $path && git fetch --all 2>&1");
        $branchs      = shell_exec(" cd $path && git branch  -r 2>&1");
        $branchs      = explode("\n", rtrim($branchs));
        $subRemoteBranch = [];
        foreach ($branchs as $branch) {
            if (strpos($branch, '/HEAD') === false) {
                $subRemoteBranch[] = trim($branch);
            }
        }
        return $subRemoteBranch;
    }
}
