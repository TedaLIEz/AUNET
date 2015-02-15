<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title></title>
</head>
<body>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title></title>
</head>
<style type="text/css">
    div#header{text-align: center;}
    div#menu {float: left;}
</style>
<body>
<div id="header">
    <h1>社团网后台管理系统</h1>
</div>
<div id="menu">
    <dl>
        <dt>社联大事记</dt>
        <dd><a href="#">大事记列表</a></dd>
        <dd><a href="#">添加预告</a></dd>
    </dl>
    <dl>
        <dt>社团地带活动预告</dt>
        <dd><a href="<?php echo U('/Admin/Forecast/forecast_index');?>">预告列表</a></dd>
        <dd><a href="<?php echo U('/Admin/Forecast/forecast_list');?>">添加预告</a></dd>
    </dl>
    <dl>
        <dt>资料上传</dt>
        <dd><a href="<?php echo U('/Admin/Upload/upload_index');?>">上传附件</a></dd>
        <dd><a href="<?php echo U('/Admin/Upload/doc_list');?>">资料列表</a></dd>
    </dl>
    <dl>
        <dt>属性管理</dt>
        <dd><a href="<?php echo U('/Admin/NewsAttribute/attr_index');?>">属性列表</a></dd>
        <dd><a href="<?php echo U('/Admin/NewsAttribute/addAttr');?>">添加属性</a></dd>
    </dl>
    <dl>
        <dt>文章管理</dt>
        <dd><a href="<?php echo U('/Admin/News/news_index');?>">文章列表</a></dd>
        <dd><a href="<?php echo U('/Admin/News/addNews');;?>">添加文章</a></dd>
        <dd><a href="<?php echo U('/Admin/News/trash');;?>">回收站</a></dd>
    </dl>
    <dl>
        <dt>权限管理</dt>
        <dd><a href="<?php echo U('/Admin/Rbac/role');;?>">角色列表</a></dd>
        <dd><a href="<?php echo U('/Admin/Rbac/user');;?>">用户列表</a></dd>
        <dd><a href="<?php echo U('/Admin/Rbac/node');;?>">节点列表</a></dd>
        <dd><a href="<?php echo U('/Admin/Rbac/addRole');;?>">添加角色</a></dd>
        <dd><a href="<?php echo U('/Admin/Rbac/addNode');;?>">添加节点</a></dd>
        <dd><a href="<?php echo U('/Admin/Rbac/addUser');;?>">添加用户</a></dd>
        <dd><a href="<?php echo U('/Admin/Rbac/logout');;?>">退出登录</a></dd>
    </dl>
    <dl>
        <dt>分类管理</dt>
        <dd><a href="<?php echo U('/Admin/Category/addCate');;?>">添加分类</a></dd>
        <dd><a href="<?php echo U('/Admin/Category/cate_index');;?>">分类列表</a></dd>
    </dl>
</div>



</body>
</html>
<form action="<?php echo U('runAddAttr');?>" method="post">
    <table class="table">
        <tr>
            <th colspan="2"><?php if($attr['id']): ?>修改博文属性<?php else: ?>添加博文属性<?php endif; ?></th>
        </tr>
        <tr>
            <td align="right">属性名称</td>
            <td>
                <input type="text" name="name"<?php if($attr['id']): ?>value="<?php echo ($attr["name"]); ?>"<?php endif; ?> />
            </td>
        </tr>
        <tr>
            <td align="right">标题颜色</td>
            <td>
                <input type="text" name="color"<?php if($attr['id']): ?>value="<?php echo ($attr["color"]); ?>"<?php endif; ?>/>
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <input type="hidden" name="id" <?php if($attr['id']): ?>value="<?php echo ($attr["id"]); ?>"<?php endif; ?>/>
                <input type="submit"<?php if($attr['id']): ?>value="保存修改"<?php else: ?>value="添加属性"<?php endif; ?>/>
            </td>
        </tr>

    </table>
</form>
</body>
</html>