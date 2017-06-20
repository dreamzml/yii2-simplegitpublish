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
    
    //跳出CsrfToken验证
    public $enableCsrfValidation = false;
    
    public function beforeAction($action) : bool {
        $res = parent::beforeAction($action);
        if ($res === false) {
            return false;
        }
        
        //if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || (
        //        !($_SERVER['PHP_AUTH_USER'] == "TESTER" && $_SERVER['PHP_AUTH_PW'] == Yii::$app->params['memc_secret_tester'])
        //        && !($_SERVER['PHP_AUTH_USER'] == "MASTER" && $_SERVER['PHP_AUTH_PW'] == Yii::$app->params['memc_secret_master'])
        //    )
        //) {
        //    Header("WWW-Authenticate: Basic realm=\"MEMC Login\"");
        //    Header("HTTP/1.0 401 Unauthorized");
        //    echo "<html><body><h1>Rejected!</h1><big>Wrong Username or Password!</big></body></html>";
        //    exit;
        //}
        
        //$this->isMaster = $_SERVER['PHP_AUTH_USER'] == "MASTER";
        return true;
    }
    
    /**
     * 引导页
     * @return [type] [description]
     */
    public function actionIndex() {

        $gitRoot = self::getGitBootPath();
        //获取所有分支
        $branchs      = shell_exec(" cd $gitRoot && git fetch && git branch -r 2>&1");

        $branchs      = explode("\n", rtrim($branchs));
        $remoteBranch = [];
        foreach ($branchs as $branch) {
            if (strpos($branch, 'origin/HEAD') === false) {
                $remoteBranch[] = trim($branch);
            }
        }
        //获取当前分支
        $currentBranch = shell_exec(" cd $gitRoot && git symbolic-ref -q HEAD 2>&1");
        $currentBranch = str_replace('refs/heads/', 'origin/', $currentBranch);
        
        return $this->renderPartial('index', [
            'remoteBranch'  => $remoteBranch,
            'currentBranch' => $currentBranch,
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
        
        return "<pre>$strout</pre>";
    }
    
    /**
     * 更新git
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
        
        $oriBranch = Yii::$app->request->get('branch');
        $branch    = str_replace('/', ' ', $oriBranch);
        $shell     = "cd $gitRoot && git pull {$branch} 2>&1";
        $strout    = "<span class='text-warning'># {$shell}</span> \n";
        $outPutCmd = shell_exec($shell);
        $strout    .= $outPutCmd;
        
        
        $mergeBranchs = Yii::$app->cache->get('merge_branchs');
        if (!YII_ENV_PROD) {
            
            //获取当前分支
            $currentBranch = shell_exec(" cd $gitRoot && git symbolic-ref -q HEAD 2>&1");
            $currentBranch = str_replace('refs/heads/', 'origin/', $currentBranch);
            
            //合并有文件冲突
            if (strpos($outPutCmd, 'Automatic merge failed;') !== false) {
                Yii::$app->response->setStatusCode(424);
                
                //重置当前分支
                $strout     .= "\n\n<span class='text-warning'>***************************************************************\n******     合并失败， 文件冲突，请解决冲突后再合并测试       ******\n***************************************************************\n</span>";
                $branchName = explode('/', $currentBranch);
                $branchName = $branchName[1] ?? $currentBranch;
                $shell      = "cd $gitRoot && git reset --hard {$currentBranch} && git checkout . && git checkout {$branchName} && git reset --hard {$currentBranch} && git pull 2>&1";
                shell_exec($shell);
                foreach ($mergeBranchs as $key => $mBranch) {
                    $mBranch = str_replace('/', ' ', $mBranch);
                    if ($mBranch == $branch) {
                        unset($mergeBranchs[$key]);
                        continue;
                    }
                    
                    $shell = "cd $gitRoot && git pull {$mBranch} 2>&1";
                    shell_exec($shell);
                }
            } elseif ($currentBranch != $oriBranch && $oriBranch != 'origin/master' && !empty($oriBranch)) {
                $mergeBranchs[] = $oriBranch;
                $mergeBranchs   = array_unique($mergeBranchs);
            }
            
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
        Yii::$app->cache->delete('merge_branchs');
        
        $branchName = explode('/', $branch);
        $branchName = $branchName[1] ?? $branch;
        
        $shell = "cd $gitRoot && git checkout . && git checkout {$branchName} && git reset --hard {$branch} && git pull 2>&1";
        
        $strout = "<span class='text-warning'># {$shell}</span> \n";
        $strout .= shell_exec($shell);
        
        return "<pre>$strout</pre>";
    }
    
    /**
     * 编译js
     * @return [type] [description]
     */
    public function actionWebpack() {
        $force = Yii::$app->request->get('force');
        
        //如果连接断开，继续执行
        ignore_user_abort(true);
        set_time_limit(0);
        
        //如果无jsx改动跳过
        if (!self::modifieldIncloudJsx() && $force != 'true') {
            return "<pre><span class='text-warning'># </span><span class='text-muted'>modified file not incloud jsx, skip webpack</span> \n</pre>";
        }
        
        //写入日志
        yii::info('webpack run star', 'githook');
        
        //git项目地址
        $gitRoot = self::getGitBootPath();
        $wwwRoot = "$gitRoot/wemall/web";
        
        $shell = "cd $wwwRoot && webpack 2>&1";
        
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
        if (!self::modifieldIncloudJsx() && $force != 'true') {
            return "<pre><span class='text-warning'># </span><span class='text-muted'>modified file not incloud jsx, skip guip</span> \n</pre>";
        }
        
        //写入日志
        yii::info('gulp run star', 'githook');
        
        //git项目地址
        $gitRoot = self::getGitBootPath();
        $wwwRoot = "$gitRoot/wemall/web";
        
        $shell = "cd $wwwRoot && gulp script 2>&1";
        
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
        //写入日志
        yii::info("git push branch {$branch} run star", 'githook');
        
        //git项目地址
        $dir        = realpath(__DIR__);
        $dir        = $dir.'/../../console/sh';
        $www_folder = realpath($dir);
        
        $shell = "cd $www_folder && sh codeJsgPush.sh \"{$branch}\" 2>&1";
        
        $strout = "<span class='text-warning'># {$shell}</span> \n";
        $strout .= shell_exec($shell);
        
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

        $gitRoot = dirname($baseDir);

        return $gitRoot;
    }
    
    /**
     * 判断未commit的文件中是否有jsx文件改动
     * @return [type] [description]
     */
    public static function modifieldIncloudJsx() {
        //git项目地址
        $gitRoot = self::getGitBootPath();
        
        $commitIds = shell_exec("cd $gitRoot && git cherry -v 2>&1");
        $commitIds = explode("\n", rtrim($commitIds));
        foreach ($commitIds as $commit) {
            $commit = explode(' ', $commit);
            if (isset($commit[1])) {
                $status = shell_exec("cd $gitRoot && git diff-tree --no-commit-id --name-only -r {$commit[1]} 2>&1");
                if (!(stripos($status, 'wemall/web/jsx/') === false))
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
}
