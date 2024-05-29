<?php
class Zip extends SnapshotDataClass{
	
	function __construct($params) {
		parent::__construct($params);
		$this->db->requiredFields = array(
				'zipcode',
				// '',
				// '',
				// 'other',
		);
		$this->db->table = 'zipCodeDetails';
		$this->db->tableJoins[0] = 'zipCodeDetails';
		$this->db->fieldsArray = array(
			'STATEFIPS'	=> @$this->params['STATEFIPS'],
			'STATE'		=> @$this->params['STATE'],
			'zipcode'	=> @$this->params['zipcode'],
			'agi_stub'	=> @$this->params['agi_stub'],
			'N1'		=> @$this->params['N1'],
			'mars1'		=> @$this->params['mars1'],
			'MARS2'		=> @$this->params['MARS2'],
			'MARS4'		=> @$this->params['MARS4'],
			'ELF'		=> @$this->params['ELF'],
			'CPREP'		=> @$this->params['CPREP'],
			'PREP'		=> @$this->params['PREP'],
			'DIR_DEP'	=> @$this->params['DIR_DEP'],
			'VRTCRIND'	=> @$this->params['VRTCRIND'],
			'N2'		=> @$this->params['N2'],
			'TOTAL_VITA'=> @$this->params['TOTAL_VITA'],
			'VITA'		=> @$this->params['VITA'],
			'TCE'		=> @$this->params['TCE'],
			'VITA_EIC'	=> @$this->params['VITA_EIC'],
			'RAC'		=> @$this->params['RAC'],
			'ELDERLY'	=> @$this->params['ELDERLY'],
			'A00100'	=> @$this->params['A00100'],
			'N02650'	=> @$this->params['N02650'],
			'A02650'	=> @$this->params['A02650'],
			'N00200'	=> @$this->params['N00200'],
			'A00200'	=> @$this->params['A00200'],
			'N00300'	=> @$this->params['N00300'],
			'A00300'	=> @$this->params['A00300'],
			'N00600'	=> @$this->params['N00600'],
			'A00600'	=> @$this->params['A00600'],
			'N00650'	=> @$this->params['N00650'],
			'A00650'	=> @$this->params['A00650'],
			'N00700'	=> @$this->params['N00700'],
			'A00700'	=> @$this->params['A00700'],
			'N00900'	=> @$this->params['N00900'],
			'A00900'	=> @$this->params['A00900'],
			'N01000'	=> @$this->params['N01000'],
			'A01000'	=> @$this->params['A01000'],
			'N01400'	=> @$this->params['N01400'],
			'A01400'	=> @$this->params['A01400'],
			'N01700'	=> @$this->params['N01700'],
			'A01700'	=> @$this->params['A01700'],
			'SCHF'		=> 	@$this->params['SCHF'],
			'N02300'	=> @$this->params['N02300'],
			'A02300'	=> @$this->params['A02300'],
			'N02500'	=> @$this->params['N02500'],
			'A02500'	=> @$this->params['A02500'],
			'N26270'	=> @$this->params['N26270'],
			'A26270'	=> @$this->params['A26270'],
			'N02900'	=> @$this->params['N02900'],
			'A02900'	=> @$this->params['A02900'],
			'N03220'	=> @$this->params['N03220'],
			'A03220'	=> @$this->params['A03220'],
			'N03300'	=> @$this->params['N03300'],
			'A03300'	=> @$this->params['A03300'],
			'N03270'	=> @$this->params['N03270'],
			'A03270'	=> @$this->params['A03270'],
			'N03150'	=> @$this->params['N03150'],
			'A03150'	=> @$this->params['A03150'],
			'N03210'	=> @$this->params['N03210'],
			'A03210'	=> @$this->params['A03210'],
			'N02910'	=> @$this->params['N02910'],
			'A02910'	=> @$this->params['A02910'],
			'N04450'	=> @$this->params['N04450'],
			'A04450'	=> @$this->params['A04450'],
			'N04100'	=> @$this->params['N04100'],
			'A04100'	=> @$this->params['A04100'],
			'N04200'	=> @$this->params['N04200'],
			'A04200'	=> @$this->params['A04200'],
			'N04470'	=> @$this->params['N04470'],
			'A04470'	=> @$this->params['A04470'],
			'A00101'	=> @$this->params['A00101'],
			'N17000'	=> @$this->params['N17000'],
			'A17000'	=> @$this->params['A17000'],
			'N18425'	=> @$this->params['N18425'],
			'A18425'	=> @$this->params['A18425'],
			'N18450'	=> @$this->params['N18450'],
			'A18450'	=> @$this->params['A18450'],
			'N18500'	=> @$this->params['N18500'],
			'A18500'	=> @$this->params['A18500'],
			'N18800'	=> @$this->params['N18800'],
			'A18800'	=> @$this->params['A18800'],
			'N18460'	=> @$this->params['N18460'],
			'A18460'	=> @$this->params['A18460'],
			'N18300'	=> @$this->params['N18300'],
			'A18300'	=> @$this->params['A18300'],
			'N19300'	=> @$this->params['N19300'],
			'A19300'	=> @$this->params['A19300'],
			'N19500'	=> @$this->params['N19500'],
			'A19500'	=> @$this->params['A19500'],
			'N19530'	=> @$this->params['N19530'],
			'A19530'	=> @$this->params['A19530'],
			'N19550'	=> @$this->params['N19550'],
			'A19550'	=> @$this->params['A19550'],
			'N19570'	=> @$this->params['N19570'],
			'A19570'	=> @$this->params['A19570'],
			'N19700'	=> @$this->params['N19700'],
			'A19700'	=> @$this->params['A19700'],
			'N20950'	=> @$this->params['N20950'],
			'A20950'	=> @$this->params['A20950'],
			'N04475'	=> @$this->params['N04475'],
			'A04475'	=> @$this->params['A04475'],
			'N04800'	=> @$this->params['N04800'],
			'A04800'	=> @$this->params['A04800'],
			'N05800'	=> @$this->params['N05800'],
			'A05800'	=> @$this->params['A05800'],
			'N09600'	=> @$this->params['N09600'],
			'A09600'	=> @$this->params['A09600'],
			'N05780'	=> @$this->params['N05780'],
			'A05780'	=> @$this->params['A05780'],
			'N07100'	=> @$this->params['N07100'],
			'A07100'	=> @$this->params['A07100'],
			'N07300'	=> @$this->params['N07300'],
			'A07300'	=> @$this->params['A07300'],
			'N07180'	=> @$this->params['N07180'],
			'A07180'	=> @$this->params['A07180'],
			'N07230'	=> @$this->params['N07230'],
			'A07230'	=> @$this->params['A07230'],
			'N07240'	=> @$this->params['N07240'],
			'A07240'	=> @$this->params['A07240'],
			'N07225'	=> @$this->params['N07225'],
			'A07225'	=> @$this->params['A07225'],
			'N07260'	=> @$this->params['N07260'],
			'A07260'	=> @$this->params['A07260'],
			'N09400'	=> @$this->params['N09400'],
			'A09400'	=> @$this->params['A09400'],
			'N85770'	=> @$this->params['N85770'],
			'A85770'	=> @$this->params['A85770'],
			'N85775'	=> @$this->params['N85775'],
			'A85775'	=> @$this->params['A85775'],
			'N10600'	=> @$this->params['N10600'],
			'A10600'	=> @$this->params['A10600'],
			'N59660'	=> @$this->params['N59660'],
			'A59660'	=> @$this->params['A59660'],
			'N59720'	=> @$this->params['N59720'],
			'A59720'	=> @$this->params['A59720'],
			'N11070'	=> @$this->params['N11070'],
			'A11070'	=> @$this->params['A11070'],
			'N10960'	=> @$this->params['N10960'],
			'A10960'	=> @$this->params['A10960'],
			'N11560'	=> @$this->params['N11560'],
			'A11560'	=> @$this->params['A11560'],
			'N11450'	=> @$this->params['N11450'],
			'A11450'	=> @$this->params['A11450'],
			'N10970'	=> @$this->params['N10970'],
			'A10970'	=> @$this->params['A10970'],
			'N10971'	=> @$this->params['N10971'],
			'A10971'	=> @$this->params['A10971'],
			'N10973'	=> @$this->params['N10973'],
			'A10973'	=> @$this->params['A10973'],
			'N06500'	=> @$this->params['N06500'],
			'A06500'	=> @$this->params['A06500'],
			'N10300'	=> @$this->params['N10300'],
			'A10300'	=> @$this->params['A10300'],
			'N85530'	=> @$this->params['N85530'],
			'A85530'	=> @$this->params['A85530'],
			'N85300'	=> @$this->params['N85300'],
			'A85300'	=> @$this->params['A85300'],
			'N11901'	=> @$this->params['N11901'],
			'A11901'	=> @$this->params['A11901'],
			'N11900'	=> @$this->params['N11900'],
			'A11900'	=> @$this->params['A11900'],
			'N11902'	=> @$this->params['N11902'],
			'A11902'	=> @$this->params['A11902'],
			'N12000'	=> @$this->params['N12000'],
			'A12000'	=> @$this->params['A12000']
		);
// $this->id = isset($this->params['zipcode']) ? $this->params['id'] : null;
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');
		});
	}
	
	/**
	  _____ ______ _______
	 / ____|  ____|__   __|
	 | |  __| |__     | |
	 | | |_ |  __|    | |
	 | |__| | |____   | |
	  \_____|______|  |_|
	 */
	function displayZip(){
		$return =array();
		$columns = array(
			'STATE'		,
			'A00100',
			'A02650',
			'zipcode'	,
			'agi_stub'	,
			'N1'		,
			'mars1'		,
			'MARS2'		,
			'MARS4'		,
			'ELF'		,
			'CPREP'		,
			'PREP'		,
			'DIR_DEP'	,
			'VRTCRIND'	,
			'N2'		,
			'TOTAL_VITA',
			'VITA'		,
			'TCE'		,
			'VITA_EIC'	,
			'RAC'		,
			'ELDERLY'	,
		);
		$this->processWhere($this->params);
		$return =array();
		$response = $this->db->select($this->db->tableJoins,$this->db->where,$this->db->whereGreater,$this->db->whereLess,$columns);
		$results 				= $response['data'];
		$this->mainQuery	 	= $this->db->fullQuery;
		$this->availableItems	= $this->db->availableItems;

		/*/
		 |--------------------------------------------------------------------------
		 |Add other details as needed
		 |--------------------------------------------------------------------------
		 */
// 		foreach ($results AS $keyIndex=>$row){
// 			$listID = $row['id'];
// 			$results[$keyIndex]['admins']	= $this->getAdminsForLists($listID);
// 			$results[$keyIndex]['contacts']	= $this->getContactsForLists($listID);
// 		}
		return  $this->prepareReturn($results);
	}
}////end of class



	/**
	  _____   ____   _____ _______
	 |  __ \ / __ \ / ____|__   __|
	 | |__) | |  | | (___    | |
	 |  ___/| |  | |\___ \   | |
	 | |    | |__| |____) |  | |
	 |_|     \____/|_____/   |_|
	 */
	// function addZip() {

// // MySQL database connection settings
// $servername = API_DB_HOST;
// $username = API_DB_USER;
// $password = API_DB_PASSWORD;
// $dbname = API_DB_NAME;

// // Create a PDO connection to the database
// try {
//     $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     echo "Connected to the database successfully\n";
// } catch (PDOException $e) {
//     echo "Connection failed: " . $e->getMessage();
//     die();
// }

// // URL of the CSV file
// $url = 'https://www.irs.gov/pub/irs-soi/20zpallagi.csv';

// // Open the CSV file
// $file = fopen($url, 'r');

// // Skip the first line (header)
// fgetcsv($file);
// // Count the number of rows to skip
// $skipRows = 166452; 
// // Skip the specified number of rows
// for ($i = 0; $i < $skipRows; $i++) {
//     fgetcsv($file);
// }

//     // Prepare the SQL statement to insert a new entry into the table
//     $sql = "INSERT INTO zipCodeDetails (
//         STATEFIPS,STATE,zipcode,agi_stub,N1,mars1,MARS2,MARS4,ELF,CPREP,PREP,DIR_DEP,VRTCRIND,N2,TOTAL_VITA,VITA,TCE,
//         VITA_EIC,RAC,ELDERLY,A00100,N02650,A02650,N00200,A00200,N00300,A00300,N00600,A00600,N00650,A00650,N00700,
//         A00700,N00900,A00900,N01000,A01000,N01400,A01400,N01700,A01700,SCHF,N02300,A02300,N02500,A02500,N26270,
//         A26270,N02900,A02900,N03220,A03220,N03300,A03300,N03270,A03270,N03150,A03150,N03210,A03210,N02910,A02910,
//         N04450,A04450,N04100,A04100,N04200,A04200,N04470,A04470,A00101,N17000,A17000,N18425,A18425,N18450,A18450,
//         N18500,A18500,N18800,A18800,N18460,A18460,N18300,A18300,N19300,A19300,N19500,A19500,N19530,A19530,N19550,
//         A19550,N19570,A19570,N19700,A19700,N20950,A20950,N04475,A04475,N04800,A04800,N05800,A05800,N09600,A09600,
//         N05780,A05780,N07100,A07100,N07300,A07300,N07180,A07180,N07230,A07230,N07240,A07240,N07225,A07225,N07260,
//         A07260,N09400,A09400,N85770,A85770,N85775,A85775,N10600,A10600,N59660,A59660,N59720,A59720,N11070,A11070,
//         N10960,A10960,N11560,A11560,N11450,A11450,N10970,A10970,N10971,A10971,N10973,A10973,N06500,A06500,N10300,
//         A10300,N85530,A85530,N85300,A85300,N11901,A11901,N11900,A11900,N11902,A11902,N12000,A12000
//     ) VALUES (
//         :STATEFIPS, :STATE, :zipcode, :agi_stub, :N1, :mars1, :MARS2, :MARS4, :ELF, :CPREP, :PREP, :DIR_DEP, :VRTCRIND,
//         :N2, :TOTAL_VITA, :VITA, :TCE, :VITA_EIC, :RAC, :ELDERLY, :A00100, :N02650, :A02650, :N00200, :A00200, :N00300,
//         :A00300, :N00600, :A00600, :N00650, :A00650, :N00700, :A00700, :N00900, :A00900, :N01000, :A01000, :N01400,
//         :A01400, :N01700, :A01700, :SCHF, :N02300, :A02300, :N02500, :A02500, :N26270, :A26270, :N02900, :A02900,
//         :N03220, :A03220, :N03300, :A03300, :N03270, :A03270, :N03150, :A03150, :N03210, :A03210, :N02910, :A02910,
//         :N04450, :A04450, :N04100, :A04100, :N04200, :A04200, :N04470, :A04470, :A00101, :N17000, :A17000, :N18425,
//         :A18425, :N18450, :A18450, :N18500, :A18500, :N18800, :A18800, :N18460, :A18460, :N18300, :A18300, :N19300,
//         :A19300, :N19500, :A19500, :N19530, :A19530, :N19550, :A19550, :N19570, :A19570, :N19700, :A19700, :N20950,
//         :A20950, :N04475, :A04475, :N04800, :A04800, :N05800, :A05800, :N09600, :A09600, :N05780, :A05780, :N07100,
//         :A07100, :N07300, :A07300, :N07180, :A07180, :N07230, :A07230, :N07240, :A07240, :N07225, :A07225, :N07260,
//         :A07260, :N09400, :A09400, :N85770, :A85770, :N85775, :A85775, :N10600, :A10600, :N59660, :A59660, :N59720,
//         :A59720, :N11070, :A11070, :N10960, :A10960, :N11560, :A11560, :N11450, :A11450, :N10970, :A10970, :N10971,
//         :A10971, :N10973, :A10973, :N06500, :A06500, :N10300, :A10300, :N85530, :A85530, :N85300, :A85300, :N11901,
//         :A11901, :N11900, :A11900, :N11902, :A11902, :N12000, :A12000
//     )";

//     // Prepare the statement
//     $stmt = $conn->prepare($sql);

// 	// Start output buffering
// ob_start();

// $counter = 0; // Counter to track loop iterations

// // Loop through the remaining lines in the CSV file
// while (($line = fgetcsv($file)) !== false) {
//     // Bind the values from the CSV line to the statement parameters
//     $stmt->bindParam(':STATEFIPS', $line[0]);
//     $stmt->bindParam(':STATE', $line[1]);
//     $stmt->bindParam(':zipcode', $line[2]);
//     $stmt->bindParam(':agi_stub', $line[3]);
//     $stmt->bindParam(':N1', $line[4]);
//     $stmt->bindParam(':mars1', $line[5]);
//     $stmt->bindParam(':MARS2', $line[6]);
//     $stmt->bindParam(':MARS4', $line[7]);
//     $stmt->bindParam(':ELF', $line[8]);
//     $stmt->bindParam(':CPREP', $line[9]);
//     $stmt->bindParam(':PREP', $line[10]);
//     $stmt->bindParam(':DIR_DEP', $line[11]);
//     $stmt->bindParam(':VRTCRIND', $line[12]);
//     $stmt->bindParam(':N2', $line[13]);
//     $stmt->bindParam(':TOTAL_VITA', $line[14]);
//     $stmt->bindParam(':VITA', $line[15]);
//     $stmt->bindParam(':TCE', $line[16]);
//     $stmt->bindParam(':VITA_EIC', $line[17]);
//     $stmt->bindParam(':RAC', $line[18]);
//     $stmt->bindParam(':ELDERLY', $line[19]);
//     $stmt->bindParam(':A00100', $line[20]);
//     $stmt->bindParam(':N02650', $line[21]);
//     $stmt->bindParam(':A02650', $line[22]);
//     $stmt->bindParam(':N00200', $line[23]);
//     $stmt->bindParam(':A00200', $line[24]);
//     $stmt->bindParam(':N00300', $line[25]);
//     $stmt->bindParam(':A00300', $line[26]);
//     $stmt->bindParam(':N00600', $line[27]);
//     $stmt->bindParam(':A00600', $line[28]);
//     $stmt->bindParam(':N00650', $line[29]);
//     $stmt->bindParam(':A00650', $line[30]);
//     $stmt->bindParam(':N00700', $line[31]);
//     $stmt->bindParam(':A00700', $line[32]);
//     $stmt->bindParam(':N00900', $line[33]);
//     $stmt->bindParam(':A00900', $line[34]);
//     $stmt->bindParam(':N01000', $line[35]);
//     $stmt->bindParam(':A01000', $line[36]);
//     $stmt->bindParam(':N01400', $line[37]);
//     $stmt->bindParam(':A01400', $line[38]);
//     $stmt->bindParam(':N01700', $line[39]);
//     $stmt->bindParam(':A01700', $line[40]);
//     $stmt->bindParam(':SCHF', $line[41]);
//     $stmt->bindParam(':N02300', $line[42]);
//     $stmt->bindParam(':A02300', $line[43]);
//     $stmt->bindParam(':N02500', $line[44]);
//     $stmt->bindParam(':A02500', $line[45]);
//     $stmt->bindParam(':N26270', $line[46]);
//     $stmt->bindParam(':A26270', $line[47]);
//     $stmt->bindParam(':N02900', $line[48]);
//     $stmt->bindParam(':A02900', $line[49]);
//     $stmt->bindParam(':N03220', $line[50]);
//     $stmt->bindParam(':A03220', $line[51]);
//     $stmt->bindParam(':N03300', $line[52]);
//     $stmt->bindParam(':A03300', $line[53]);
//     $stmt->bindParam(':N03270', $line[54]);
//     $stmt->bindParam(':A03270', $line[55]);
//     $stmt->bindParam(':N03150', $line[56]);
//     $stmt->bindParam(':A03150', $line[57]);
//     $stmt->bindParam(':N03210', $line[58]);
//     $stmt->bindParam(':A03210', $line[59]);
//     $stmt->bindParam(':N02910', $line[60]);
//     $stmt->bindParam(':A02910', $line[61]);
//     $stmt->bindParam(':N04450', $line[62]);
//     $stmt->bindParam(':A04450', $line[63]);
//     $stmt->bindParam(':N04100', $line[64]);
//     $stmt->bindParam(':A04100', $line[65]);
//     $stmt->bindParam(':N04200', $line[66]);
//     $stmt->bindParam(':A04200', $line[67]);
//     $stmt->bindParam(':N04470', $line[68]);
//     $stmt->bindParam(':A04470', $line[69]);
//     $stmt->bindParam(':A00101', $line[70]);
//     $stmt->bindParam(':N17000', $line[71]);
//     $stmt->bindParam(':A17000', $line[72]);
//     $stmt->bindParam(':N18425', $line[73]);
//     $stmt->bindParam(':A18425', $line[74]);
//     $stmt->bindParam(':N18450', $line[75]);
//     $stmt->bindParam(':A18450', $line[76]);
//     $stmt->bindParam(':N18500', $line[77]);
//     $stmt->bindParam(':A18500', $line[78]);
//     $stmt->bindParam(':N18800', $line[79]);
//     $stmt->bindParam(':A18800', $line[80]);
//     $stmt->bindParam(':N18460', $line[81]);
//     $stmt->bindParam(':A18460', $line[82]);
//     $stmt->bindParam(':N18300', $line[83]);
//     $stmt->bindParam(':A18300', $line[84]);
//     $stmt->bindParam(':N19300', $line[85]);
//     $stmt->bindParam(':A19300', $line[86]);
//     $stmt->bindParam(':N19500', $line[87]);
//     $stmt->bindParam(':A19500', $line[88]);
//     $stmt->bindParam(':N19530', $line[89]);
//     $stmt->bindParam(':A19530', $line[90]);
//     $stmt->bindParam(':N19550', $line[91]);
//     $stmt->bindParam(':A19550', $line[92]);
//     $stmt->bindParam(':N19570', $line[93]);
//     $stmt->bindParam(':A19570', $line[94]);
//     $stmt->bindParam(':N19700', $line[95]);
//     $stmt->bindParam(':A19700', $line[96]);
//     $stmt->bindParam(':N20950', $line[97]);
//     $stmt->bindParam(':A20950', $line[98]);
//     $stmt->bindParam(':N04475', $line[99]);
//     $stmt->bindParam(':A04475', $line[100]);
//     $stmt->bindParam(':N04800', $line[101]);
//     $stmt->bindParam(':A04800', $line[102]);
//     $stmt->bindParam(':N05800', $line[103]);
//     $stmt->bindParam(':A05800', $line[104]);
//     $stmt->bindParam(':N09600', $line[105]);
//     $stmt->bindParam(':A09600', $line[106]);
//     $stmt->bindParam(':N05780', $line[107]);
//     $stmt->bindParam(':A05780', $line[108]);
//     $stmt->bindParam(':N07100', $line[109]);
//     $stmt->bindParam(':A07100', $line[110]);
//     $stmt->bindParam(':N07300', $line[111]);
//     $stmt->bindParam(':A07300', $line[112]);
//     $stmt->bindParam(':N07180', $line[113]);
//     $stmt->bindParam(':A07180', $line[114]);
//     $stmt->bindParam(':N07230', $line[115]);
//     $stmt->bindParam(':A07230', $line[116]);
//     $stmt->bindParam(':N07240', $line[117]);
//     $stmt->bindParam(':A07240', $line[118]);
//     $stmt->bindParam(':N07225', $line[119]);
//     $stmt->bindParam(':A07225', $line[120]);
//     $stmt->bindParam(':N07260', $line[121]);
//     $stmt->bindParam(':A07260', $line[122]);
//     $stmt->bindParam(':N09400', $line[123]);
//     $stmt->bindParam(':A09400', $line[124]);
//     $stmt->bindParam(':N85770', $line[125]);
//     $stmt->bindParam(':A85770', $line[126]);
//     $stmt->bindParam(':N85775', $line[127]);
//     $stmt->bindParam(':A85775', $line[128]);
//     $stmt->bindParam(':N10600', $line[129]);
//     $stmt->bindParam(':A10600', $line[130]);
//     $stmt->bindParam(':N59660', $line[131]);
//     $stmt->bindParam(':A59660', $line[132]);
//     $stmt->bindParam(':N59720', $line[133]);
//     $stmt->bindParam(':A59720', $line[134]);
//     $stmt->bindParam(':N11070', $line[135]);
//     $stmt->bindParam(':A11070', $line[136]);
//     $stmt->bindParam(':N10960', $line[137]);
//     $stmt->bindParam(':A10960', $line[138]);
//     $stmt->bindParam(':N11560', $line[139]);
//     $stmt->bindParam(':A11560', $line[140]);
//     $stmt->bindParam(':N11450', $line[141]);
//     $stmt->bindParam(':A11450', $line[142]);
//     $stmt->bindParam(':N10970', $line[143]);
//     $stmt->bindParam(':A10970', $line[144]);
//     $stmt->bindParam(':N10971', $line[145]);
//     $stmt->bindParam(':A10971', $line[146]);
//     $stmt->bindParam(':N10973', $line[147]);
//     $stmt->bindParam(':A10973', $line[148]);
//     $stmt->bindParam(':N06500', $line[149]);
//     $stmt->bindParam(':A06500', $line[150]);
//     $stmt->bindParam(':N10300', $line[151]);
//     $stmt->bindParam(':A10300', $line[152]);
//     $stmt->bindParam(':N85530', $line[153]);
//     $stmt->bindParam(':A85530', $line[154]);
//     $stmt->bindParam(':N85300', $line[155]);
//     $stmt->bindParam(':A85300', $line[156]);
//     $stmt->bindParam(':N11901', $line[157]);
//     $stmt->bindParam(':A11901', $line[158]);
//     $stmt->bindParam(':N11900', $line[159]);
//     $stmt->bindParam(':A11900', $line[160]);
//     $stmt->bindParam(':N11902', $line[161]);
//     $stmt->bindParam(':A11902', $line[162]);
//     $stmt->bindParam(':N12000', $line[163]);
//     $stmt->bindParam(':A12000', $line[164]);

//     // Execute the statement
//     $stmt->execute();
// 	    $counter++;

//     // Flush the output every 10,000 loops
//     if ($counter % 10000 == 0) {
//         ob_flush();
//         flush();
//     }
// }

// 	// Close the CSV file
// 	fclose($file);
// 	ob_end_clean();
// 	// Close the database connection
// 	$conn = null;
// 	$this->displayZip();

// 	}
	
// 	/**
// 	  _____  _    _ _______
// 	 |  __ \| |  | |__   __|
// 	 | |__) | |  | |  | |
// 	 |  ___/| |  | |  | |
// 	 | |    | |__| |  | |
// 	 |_|     \____/   |_|
// 	 */
// 	function updateZips(){
// 		if (!empty($this->id)) {
// 			// Update the record with the given ID
// 			$updated = $this->db->updateRecord($this->db->table, $this->id);

// 			if ($updated['status']!=='error') {
// 				// If the update is successful, return the displayed Zips
// 				return $this->displayZip();
// 			} else {
// 				// If there is an error during update, return an error message
// 				return array('error' => __LINE__.": Could not update record", 'details' => $updated['message']);
// 			}
// 		} else {
// 			// If no valid ID is given, return an error message
// 			return array('error' => "no valid ID given: $this->id");
// 		}
// 	}
	
// 	/**
// 	  _____  ______ _      ______ _______ ______
// 	 |  __ \|  ____| |    |  ____|__   __|  ____|
// 	 | |  | | |__  | |    | |__     | |  | |__
// 	 | |  | |  __| | |    |  __|    | |  |  __|
// 	 | |__| | |____| |____| |____   | |  | |____
// 	 |_____/|______|______|______|  |_|  |______|
// 	 I rarely delete records, so I deactive them instead.
// 	 You may want to delete them so adjust as needed.
// 	 */
// 	function removeZips(){
// 		if (is_array($this->id)){
// 			$returnArray = array();
// 			foreach ($this->id AS $recordID){
// 				$returnArray[] = $this->db->deactivate($recordID);
// 			}
// 		}
// 		else {
// 			$returnArray = array($this->db->deactivate($this->id));
// 		}
// 		return $this->prepareReturn($returnArray);
// 	}
	
