<?php
	function getmicrotime(){
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}
	$start = getmicrotime();

        require_once ('config.inc.php');
	require_once ('pdbc/DriverManager.class.php');

	# ��������mysql4.1���°汾��url
	#$url = 'pdbc:mysql://localhost:3306/shinestb?dbuser=root&dbpass=shinestb';
	$url = $GLOBALS['url'];
	# ��������mysql4.1���ϰ汾��url
	#$url = 'pdbc:mysqli://localhost:3306/shinestb?dbuser=root&dbpass=';
	# ��������oracle��url
	#$url = 'pdbc:oracle://localhost:3306/shinestb?dbuser=root&dbpass=';
	# ��������sql server��url
	#$url = 'pdbc:mssql://localhost:3306/shinestb?dbuser=root&dbpass=';

	# ����Connection���󣬻�ȡһ�����ݿ�����
	$conn = DriverManager::getConnection($url);
	# �������ݿ�Ϊ�Զ�����ģʽ
	$conn->setAutoCommit(true);

	# ��ӡpdbc�汾
	printf ('PDBC  Version: %s<br/>' ,$conn->getVersion());
	# ��ӡmysql�汾
	printf ('MySQL Version: %d(%s)<br/>', $conn->getServerVersionInt(), $conn->getServerVersionString());
	
	$sql = 'SELECT id, name, add_date FROM program ORDER BY id ASC LIMIT 3';
	
	/*
	# preparedStatementģʽ
	$pstmt = $conn->prepareStatement($sql);
	$rs = $pstmt->executeQuery();
	*/

	# Statementģʽ
	$stmt = $conn->createStatement();

	# ���ò�ѯ������Ĵ�С
	$stmt->setFetchSize("2.5");
	# ��ȡ��ѯ������Ĵ�С
	$stmt->pGgetFetchSize();
	# ִ��SQL��ѯ
	$stmt->executeQuery($sql);
	# ��ȡ���β�ѯ�Ľ����
	$rs = $stmt->getResultSet();

	# ��ӡ��Ӱ��ļ�¼��(ֻ����DELETE,INSERT,UPDATE���)
	printf ('Update Count: %s<br/>', $stmt->getUpdateCount());

	# ��ӡ���β�ѯ���ֶ���Ϣ
	var_dump($rs->pGetFetchFieldInfo());
	print '<br/>';

	# ��ȡ���β�ѯ��Ԫ����
	#$metadata = $rs->getMetaData();
	#print_r($metadata);
	#print_r($rs->getMetaStructure());exit;
	print '<br/>';

	# ���������
	while ($rs->next()) {
		# $rs->pGetCursor() ���ص�ǰ�α�
		# $rs->getString('name') ��ȡ����ΪString���ֶ���Ϊname��ֵ
		printf ('%s $rs->getString(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	}

	# ���α��ƶ���������в���ָ��λ��[$rs->absolute(1)]
	if ($rs->absolute(1))
		printf ('%s $rs->absolute(1): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	if ($rs->absolute(2))
		printf ('%s $rs->absolute(2): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	
	# ���α��ƶ�������������һλ[$rs->absolute(1)]
	if ($rs->last())
		printf ('%s $rs->last(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	# �жϵ�ǰ�α��Ƿ�Ϊ����������һ��
	if ($rs->isLast())
		printf ('%s $rs->isLast(): %s<br/>', $rs->pGetCursor(), 'isLast');

	# ���α��ƶ�����ǰ�α��ǰһλ
	if ($rs->previous())
		printf ('%s $rs->previous(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	if ($rs->previous())
		printf ('%s $rs->previous(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));

	while ($rs->next()) {
		printf ('%s $rs->getString(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	}

	# ���α��ƶ������������λ
	if ($rs->first())
		printf ('%s $rs->first(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	# �жϵ�ǰ�α��Ƿ�Ϊ�����������
	if ($rs->isFirst())
		printf ('%s $rs->isFirst(): %s<br/>', $rs->pGetCursor(), 'isFirst');

	$rs->close();

	# �������ִ�е�SQL���
	print '<br/>��ʼ����ִ��SQL(Statement->addBatch && Statement->executeBatch)<br/>';
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
	
	# �ر�PreparedStatement����
	#$pstmt->close();
	# �ر�Statement����
	$stmt->close();
	# �ر�ResultSet���󣬲��ͷŽ����
	$rs->close();
	
	# ���õ�ǰConnection����Ϊֻ������
	$conn->setReadOnly(false);
	# �жϵ�ǰConnection�����Ƿ�Ϊֻ������
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
	# ����PreparedStatement����
	$pstmt = $conn->prepareStatement($sql);
	# ����Ԥ��ѯ���ĵ�1������(?)
	$pstmt->setInt(1, 100);
	# ����Ԥ��ѯ���ĵ�2������(?)
	$pstmt->setInt(2, 5);
	# ִ�в�ѯ(����SELECT����ʹ�ø÷���)
	$rs = $pstmt->executeQuery();

	while ($rs->next()) {
		printf ('%s $rs->getString(): %s<br/>', $rs->pGetCursor(), $rs->getString('name'));
	}

	# �ر�PreparedStatement����
	$pstmt->close();
	$rs->close();

	print '<br/>��ʼ����ִ��SQL(PreparedStatement->addBatch && PreparedStatement->executeBatch)<br/>';
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
	# ִ��SQL���(����INSERT, UPDATE, DELETE����ʹ�ø÷���)
	$ret = $pstmt->executeUpdate();
	var_dump($ret);
	printf ('<br/>Update Count: %s<br/>', $pstmt->getUpdateCount());
	*/

	$pstmt->close();
	# �ر�Connection���󣬶Ͽ����ݿ�����
	$conn->close();

	$end = getmicrotime();
	#print '<br/>'.(($end - $start)*1000).' ����';
	print '<br/>'.($end - $start).' ��';
	exit();
?>