<?php
	function getmicrotime(){
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}
	$start = getmicrotime();

        require_once ('config.inc.php');
	require_once ('pdbc/DriverManager.class.php');

	# 用来连接mysql4.1以下版本的url
	#$url = 'pdbc:mysql://localhost:3306/shinestb?dbuser=root&dbpass=shinestb';
	$url = $GLOBALS['url'];
	# 用来连接mysql4.1以上版本的url
	#$url = 'pdbc:mysqli://localhost:3306/shinestb?dbuser=root&dbpass=';
	# 用来连接oracle的url
	#$url = 'pdbc:oracle://localhost:3306/shinestb?dbuser=root&dbpass=';
	# 用来连接sql server的url
	#$url = 'pdbc:mssql://localhost:3306/shinestb?dbuser=root&dbpass=';

	# 创建Connection对象，获取一个数据库连接
	$conn = DriverManager::getConnection($url);
	# 设置数据库为自动更新模式
	$conn->setAutoCommit(true);

	# 打印pdbc版本
	printf ('PDBC  Version: %s<br/>' ,$conn->getVersion());
	# 打印mysql版本
	printf ('MySQL Version: %d(%s)<br/>', $conn->getServerVersionInt(), $conn->getServerVersionString());
	
	$sql = 'SELECT id, name, add_date FROM program ORDER BY id ASC LIMIT 3';
	
	/*
	# preparedStatement模式
	$pstmt = $conn->prepareStatement($sql);
	$rs = $pstmt->executeQuery();
	*/

	# Statement模式
	$stmt = $conn->createStatement();

	# 设置查询结果集的大小
	$stmt->setFetchSize("2.5");
	# 获取查询结果集的大小
	$stmt->pGgetFetchSize();
	# 执行SQL查询
	$stmt->executeQuery($sql);
	# 获取本次查询的结果集
	$rs = $stmt->getResultSet();

	# 打印收影响的记录数(只限于DELETE,INSERT,UPDATE语句)
	printf ('Update Count: %s<br/>', $stmt->getUpdateCount());

	# 打印本次查询的字段信息
	var_dump($rs->pGetFetchFieldInfo());
	print '<br/>';

	# 获取本次查询的元数据
	#$metadata = $rs->getMetaData();
	#print_r($metadata);
	#print_r($rs->getMetaStructure());exit;
	print '<br/>';

	# 遍历结果集
	while ($rs->next()) {
		# $rs->pGetCursor() 返回当前游标
		# $rs->getString('name') 获取类型为String的字段名为name的值
		printf ('%s $rs->getString(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	}

	# 将游标移动至结果集中参数指定位置[$rs->absolute(1)]
	if ($rs->absolute(1))
		printf ('%s $rs->absolute(1): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	if ($rs->absolute(2))
		printf ('%s $rs->absolute(2): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	
	# 将游标移动至结果集中最后一位[$rs->absolute(1)]
	if ($rs->last())
		printf ('%s $rs->last(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	# 判断当前游标是否为结果集中最后一条
	if ($rs->isLast())
		printf ('%s $rs->isLast(): %s<br/>', $rs->pGetCursor(), 'isLast');

	# 将游标移动至当前游标的前一位
	if ($rs->previous())
		printf ('%s $rs->previous(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	if ($rs->previous())
		printf ('%s $rs->previous(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));

	while ($rs->next()) {
		printf ('%s $rs->getString(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	}

	# 将游标移动至结果集中首位
	if ($rs->first())
		printf ('%s $rs->first(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	# 判断当前游标是否为结果集中首条
	if ($rs->isFirst())
		printf ('%s $rs->isFirst(): %s<br/>', $rs->pGetCursor(), 'isFirst');

	$rs->close();

	# 添加批量执行的SQL语句
	print '<br/>开始批量执行SQL(Statement->addBatch && Statement->executeBatch)<br/>';
	$sql1 = 'SELECT * FROM program ORDER BY id DESC LIMIT 4';
	$sql2 = 'SELECT * FROM room ORDER BY id DESC LIMIT 4';
	$sql3 = 'INSERT INTO vodfee2 (RoomNumber, FeeDatetime, fee, flag) VALUES("1204", NOW(), "124", "1")';
	$stmt->addBatch($sql1);
	$stmt->addBatch($sql2);
	$stmt->addBatch($sql3);
	$batch = array($sql1, $sql2, $sql3);
	$stmt->addBatch($batch);
	#print_r($stmt->getBatchSQL());
	$rss = $stmt->executeBatch();
	#var_dump($stmt->resultBatch);exit;
	$stmt->getMoreResults();
	$rs = $stmt->getResultSet();
	#var_dump($rs);
	#print_r($rs->getMetaStructure());
	while ($rs->next()) {
		printf ('%s $rs->getString(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	}
	$rs->close();
	
	$stmt->getMoreResults();
	$rs = $stmt->getResultSet();
	#var_dump($rs);
	while ($rs->next()) {
		printf ('%s $rs->getString(): %s<br/>', $rs->pGetCursor(), $rs->getString('stbserial'));
	}
	#print_r($rss);
	
	# 关闭PreparedStatement对象
	#$pstmt->close();
	# 关闭Statement对象
	$stmt->close();
	# 关闭ResultSet对象，并释放结果集
	$rs->close();
	
	# 设置当前Connection对象为只读属性
	$conn->setReadOnly(false);
	# 判断当前Connection对象是否为只读属性
	if ($conn->isReadOnly())
		printf ('%s', 'readonly<br/>');

	$sql = 'UPDATE program SET last_modify = NOW() WHERE id < 80';
	$stmt = $conn->createStatement();
	$stmt->executeUpdate($sql);
	
	printf ('Update Count: %s<br/>', $stmt->getUpdateCount());
	
	$stmt->close();

	$conn->setReadOnly();
	if ($conn->isReadOnly())
		printf ('%s', 'set connection readonly<br/>');

	$sql = 'SELECT id, name FROM program WHERE id > ? ORDER BY id ASC LIMIT ?';
	# 创建PreparedStatement对象
	$pstmt = $conn->prepareStatement($sql);
	# 设置预查询语句的第1个参数(?)
	$pstmt->setInt(1, 100);
	# 设置预查询语句的第2个参数(?)
	$pstmt->setInt(2, 5);
	# 执行查询(对于SELECT操作使用该方法)
	$rs = $pstmt->executeQuery();

	while ($rs->next()) {
		printf ('%s $rs->getString(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	}

	# 关闭PreparedStatement对象
	$pstmt->close();
	$rs->close();

	print '<br/>开始批量执行SQL(PreparedStatement->addBatch && PreparedStatement->executeBatch)<br/>';
	$sql = 'INSERT INTO news_type (name) VALUES(?)';
	#print $sql.'<br/>';
	$pstmt = $conn->prepareStatement($sql);
	$pstmt->setString(1, 'test');
	$pstmt->addBatch();

	#$sql = 'INSERT INTO news_type (name) VALUES(?)';
	$sql = 'SELECT * FROM program';
	#print $sql.'<br/>';
	$pstmt = $conn->prepareStatement($sql);
	$pstmt->setString(1, 'chenxi');
	$pstmt->addBatch();
	#$rss = $pstmt->executeBatch();
	#print_r($pstmt->sqlBatch);
	#print_r($rss);

	/*
	# 执行SQL语句(对于INSERT, UPDATE, DELETE操作使用该方法)
	$ret = $pstmt->executeUpdate();
	var_dump($ret);
	printf ('<br/>Update Count: %s<br/>', $pstmt->getUpdateCount());
	*/

	$pstmt->close();
	# 关闭Connection对象，断开数据库连接
	$conn->close();

	$end = getmicrotime();
	#print '<br/>'.(($end - $start)*1000).' 毫秒';
	print '<br/>'.($end - $start).' 秒';
	exit();
?>