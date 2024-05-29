<?php
class Stats extends SnapshotDataClass
{
    private $objectName;
    private $validObjects = ENDPOINT_OBJECTS;

    function __construct($params, $authJWTstring = null)
    {
        parent::__construct($params, $authJWTstring, false);

       // $this->accessClientListArray['POST']['admin']   = true;
		//$this->accessClientListArray['POST']['all']     = true;
		//$this->accessClientListArray['POST']['user']    = true;
		$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		//$this->accessClientListArray['GET']['user']     = true;
		//$this->accessClientListArray['PUT']['admin']    = true;
		// $this->accessClientListArray['PUT']['all']      = true;
		//$this->accessClientListArray['PUT']['user']     = true;
		//$this->accessClientListArray['DELETE']['admin'] = true; //manually do to avoid any accidental deactivations
		// $this->accessClientListArray['DELETE']['all']   = true;
		//$this->accessClientListArray['DELETE']['user']  = true;

    }


    /**
        _____ ______ _______
       / ____|  ____|__   __|
      | |  __| |__     | |
      | | |_ |  __|    | |
      | |__| | |____   | |
       \_____|______|  |_|
     */
    function displayStats()
    {
        //die('step one');
        // Check if $this->params has 'secondObjectName' or 'stat'
        //todo change socondObjectName to foreignObjectKey or something cool like that
        if (isset($this->params['secondObjectName'])) {

            $objectName = $this->params['objectName'];

            $id = $this->params['id'];

            $secondObjectName = $this->params['secondObjectName'];

            $result = $this->fetchTwoStatData($objectName, $id, $secondObjectName);

            // Check the result
            if ($result['status'] === 'success') {
                // Access the data
                $data = $result['data']['data'];
                // Process the data as needed
                return $data;
            } else {
                // Handle the case where fetching the stat data was not successful
                return ['error' => $result['message']];
            }


        } elseif (isset($this->params['stat'])) {
            $statType = $this->params['stat'];
            $result = $this->fetchSingleStatData($statType);


            if (isset($this->params['details'])) {
                $details = $this->fetchDetailsofSpecificStat($statType);
                $result['data']['data'][0]['statsForGeeks'] = $details['data']['data'][0];
            }
            if ($result['status'] === 'success') {
                // Access the data
                $data = $result['data']['data'][0];
                //unset($result['data'][0]['availableItems']); Todo unset debug
                // Process the data as needed
                return $data;
            } else {
                // Handle the case where fetching the stat data was not successful
                return ['error' => $result['message']];
            }
        } elseif (isset($this->params['objectName'])) {
            $objectName = $this->params['objectName'];
            $tables = $this->params['objectName'];
            $whereArray = [];
            $response = $this->db->select($tables, $whereArray /*,null,null,$columnsArray*/);
            $rowCount = count($response['data']);
            $finalResponse = ['objectName' => $objectName, 'rowCount' => $rowCount];
            return $finalResponse;
        } else {
            // Neither 'secondObjectName' nor 'stat' is present in $this->params
            return ['error' => 'Unsupported stat type.'];
        }





    }
    /**
             _____       _        _ _     
            |  __ \     | |      (_) |    
            | |  | | ___| |_ __ _ _| |___ 
            | |  | |/ _ \ __/ _` | | / __|
            | |__| |  __/ || (_| | | \__ \
            |_____/ \___|\__\__,_|_|_|___/

     */
    function fetchDetailsofSpecificStat($statType)
    {
        $gamesStartedOnSundayQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE DAYOFWEEK(gameStartDateTime) = 1)
        ';
        $gamesStartedOnMondayQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE DAYOFWEEK(gameStartDateTime) = 2)
                ';
        $gamesStartedOnTuesdayQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE DAYOFWEEK(gameStartDateTime) = 3)
        ';

        $gamesStartedOnWednesdayQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE DAYOFWEEK(gameStartDateTime) = 4)
        ';

        $gamesStartedOnThursdayQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE DAYOFWEEK(gameStartDateTime) = 5)
        ';

        $gamesStartedOnFridayQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE DAYOFWEEK(gameStartDateTime) = 6)
        ';

        $gamesStartedOnSaturdayQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE DAYOFWEEK(gameStartDateTime) = 7)
        ';
        $gamesStartedInJanuaryQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 1)
        ';

        $gamesStartedInFebruaryQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 2)
        ';

        $gamesStartedInMarchQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 3)
        ';

        $gamesStartedInAprilQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 4)
        ';

        $gamesStartedInMayQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 5)
        ';

        $gamesStartedInJuneQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 6)
        ';

        $gamesStartedInJulyQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 7)
        ';

        $gamesStartedInAugustQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 8)
        ';

        $gamesStartedInSeptemberQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 9)
        ';

        $gamesStartedInOctoberQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 10)
        ';

        $gamesStartedInNovemberQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 11)
        ';

        $gamesStartedInDecemberQuery = '
            (SELECT COUNT(*)
            FROM games
            WHERE MONTH(gameStartDateTime) = 12)
        ';
        switch ($statType) {
            case 'totalGames':

                // Main query parameters
                $columnsArray = array(
                    "$gamesStartedOnSundayQuery AS Games_Started_On_Sunday",
                    "$gamesStartedOnMondayQuery AS Games_Started_On_Monday",
                    "$gamesStartedOnTuesdayQuery AS Games_Started_On_Tuesday",
                    "$gamesStartedOnWednesdayQuery AS Games_Started_On_Wednesday",
                    "$gamesStartedOnThursdayQuery AS Games_Started_On_Thursday",
                    "$gamesStartedOnFridayQuery AS Games_Started_On_Friday",
                    "$gamesStartedOnSaturdayQuery AS Games_Started_On_Saturday",
                    "$gamesStartedInJanuaryQuery AS Games_Started_In_January",
                    "$gamesStartedInFebruaryQuery AS Games_Started_In_February",
                    "$gamesStartedInMarchQuery AS Games_Started_In_March",
                    "$gamesStartedInAprilQuery AS Games_Started_In_April",
                    "$gamesStartedInMayQuery AS Games_Started_In_May",
                    "$gamesStartedInJuneQuery AS Games_Started_In_June",
                    "$gamesStartedInJulyQuery AS Games_Started_In_July",
                    "$gamesStartedInAugustQuery AS Games_Started_In_August",
                    "$gamesStartedInSeptemberQuery AS Games_Started_In_September",
                    "$gamesStartedInOctoberQuery AS Games_Started_In_October",
                    "$gamesStartedInNovemberQuery AS Games_Started_In_November",
                    "$gamesStartedInDecemberQuery AS Games_Started_In_December",
                );

                $whereArray = array(
                );

                // Your main query parameters
                $tables = array("dual");
                // Your select function call
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;



            default:
                // Unsupported stat type
                return ['status' => 'error', 'message' => 'Unsupported stat type.'];
        }
    }

    // Sets specific stats here (single object based)
    /**
         ____                _____ _        _    
        / __ \              / ____| |      | |   
       | |  | |_ __   ___  | (___ | |_ __ _| |_  
       | |  | | '_ \ / _ \  \___ \| __/ _` | __| 
       | |__| | | | |  __/  ____) | || (_| | |_  
        \____/|_| |_|\___| |_____/ \__\__,_|\__| 
    */
    function fetchSingleStatData($statType)
    {
        //here are all of the queries listed outside of the individual cases so they can be used multiple times as needed
        $averageSnapshotsPerUserQuery = '
                (SELECT AVG(snapshot_count) AS average_snapshots_per_user
                FROM (
                    SELECT COUNT(*) AS snapshot_count
                    FROM snapshots
                    GROUP BY userID
                ) AS snapshot_counts)
                    ';
        $averageUsersPerGameQuery =
            '(SELECT AVG(user_count) AS average_users_per_game
                    FROM (
                        SELECT gameID, COUNT(DISTINCT userID) AS user_count
                        FROM gamesXusers
                        GROUP BY gameID
                    ) AS user_counts)
                    ';

        $userWithMostSnapshotsTakenQuery = "
                (SELECT userID
                FROM (
                    SELECT userID, COUNT(*) AS snapshot_count
                    FROM snapshots
                    GROUP BY userID
                    ORDER BY snapshot_count DESC
                    LIMIT 1
                ) AS max_snapshot_user
                )
                ";
        $numberOfUsersInGamesCountQuery = "(SELECT COUNT(DISTINCT userID) AS total_registered_users
                FROM gamesXusers)
                ";

        $totalUsersQuery = "(SELECT COUNT(*) FROM users)";
        $totalGamesCountQuery = '(SELECT COUNT(*) AS total_games
                FROM games)
                ';
        $totalActiveGamesCountQuery = "(SELECT COUNT(*) AS total_active_games
                FROM games
                WHERE gameStatusID = '7'
                )";

        $totalSnapshotsCountQuery = "(SELECT COUNT(*) AS total_snapshots
                FROM snapshots)
                ";
        $totalGamesPlayedQuery = "(SELECT COUNT(*) AS total_games_played
                FROM games
                WHERE gameStatusID = '3'
                )";
        $averageHoursPerGameQuery = "(SELECT AVG(TIMESTAMPDIFF(HOUR, gameStartDateTime, gameEndDateTime)) AS average_hours_per_game
                FROM games
                WHERE gameStatusID = '3' AND gameEndDateTime IS NOT NULL
                )";
        $averageSnapshotsPerGameQuery = "(SELECT AVG(snapshot_count) AS average_snapshots_per_game
                FROM (
                    SELECT gamesXusers.gameID, COUNT(*) AS snapshot_count
                    FROM gamesXusers
                    INNER JOIN snapshots ON gamesXusers.userID = snapshots.userID
                    GROUP BY gamesXusers.gameID
                ) AS snapshot_counts)";
        $userWithMostHoursPlayedQuery = "(SELECT userID
                FROM (
                    SELECT userID, SUM(TIMESTAMPDIFF(HOUR, gameStartDateTime, COALESCE(gameEndDateTime, NOW()))) AS total_hours_played
                    FROM gamesXusers
                    JOIN games ON gamesXusers.gameID = games.id
                    GROUP BY userID
                    ORDER BY total_hours_played DESC
                    LIMIT 1
                ) AS most_hours_played_user
                )";
        $userWithMostGamesPlayedQuery = "(SELECT userID
                FROM (
                    SELECT userID, COUNT(*) AS total_games_played
                    FROM gamesXusers
                    GROUP BY userID
                    ORDER BY total_games_played DESC
                    LIMIT 1
                ) AS user_with_most_games_played
                )";
        $totalHoursOfGameplayQuery = "(SELECT SUM(TIMESTAMPDIFF(HOUR, gameStartDateTime, COALESCE(gameEndDateTime, NOW()))) AS total_hours_of_gameplay
                FROM games
                )";
        $totalNewUsersByDayQuery = "(SELECT COUNT(*)FROM users WHERE DATE(ts) = CURDATE() - INTERVAL 1 DAY)";
        $totalNewUsersByWeekQuery = "(SELECT COUNT(*) AS new_Users_last_week FROM users WHERE DATE(ts) >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 WEEK))";
        $totalNewUsersByMonthQuery = "(SELECT COUNT(*) AS new_Users_last_month FROM users WHERE DATE(ts) >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
        $totalNewUsersByYearQuery = "(SELECT COUNT(*) AS new_Users_last_year FROM users WHERE DATE(ts) >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 YEAR))";

        // Implement logic to fetch data based on the stat type
        switch ($statType) {
            case 'public':

                // Main query parameters
                $columnsArray = array(
                    "$totalUsersQuery AS total_Users",
                    "$numberOfUsersInGamesCountQuery AS 'total_users_in_games'",
                    "$totalGamesCountQuery AS 'total_games'",
                    "$totalActiveGamesCountQuery AS 'total_active_games'",
                    "$totalSnapshotsCountQuery AS 'total_snapshots'",
                    "$averageUsersPerGameQuery AS 'average_users_per_game'",
                    "$totalGamesPlayedQuery AS 'total_games_played'",
                    "$averageHoursPerGameQuery AS 'average_hours_per_game'", //currently untested
                    "$averageSnapshotsPerGameQuery AS 'average_snapshots_per_game'",
                    "$averageSnapshotsPerUserQuery AS 'average_snapshots_per_user'",
                    "$userWithMostHoursPlayedQuery AS 'user_with_most_hours_played'",
                    "$userWithMostSnapshotsTakenQuery AS 'user_with_most_snapshots_taken'",
                    "$userWithMostGamesPlayedQuery AS 'user_with_most_games_played'",
                    "$totalHoursOfGameplayQuery AS 'total_hours_of_gameplay'", //untested
                    // Add other columns as needed
                );
                $whereArray = array(
                );

                // Your main query parameters
                $tables = array("dual");
                // Your select function call
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                // header("Content-Type: application/json");
                // echo json_encode([
                //     "Line " . __LINE__ => __FILE__,
                //     'function' => debug_backtrace()[1]['function'] ?? 'N/A',
                //     "zzReturn" => $result,
                // ]);
                // die();
                return ['status' => 'success', 'data' => $result];

                break;

            case 'newUsers':
                $columnsArray = array(
                    "$totalNewUsersByDayQuery AS 'new_Users_yesterday'",
                    "$totalNewUsersByWeekQuery AS 'new_Users_Last_Week'",
                    "$totalNewUsersByMonthQuery AS 'new_Users_Last_Month'",
                    "$totalNewUsersByYearQuery AS 'new_Users_Last_Year'",

                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                unset($result['data'][0]['availableItems']);
                return ['status' => 'success', 'data' => $result];

                if ($result && isset($result['data'][0])) {
                    return [
                        'gameID' => $result['data'][0]['gameID'],
                        'numberOfPlayers' => $result['data'][0]['numberOfPlayers'],
                    ];
                }
                break;

            case 'paidUsers': // dont worry about this one
                $tables = array("users");
                $whereArray = array("userRoleID" => 2);
                $columnsArray = array('COUNT(DISTINCT id) AS numberOfPlayers');
                $whereMoreString = '';
                $groupByColumnBoolean = false;
                $justDataBoolean = false;
                $numberPerPage = 'all';
                $pageNumber = null;
                $orderBy = 'id';
                $order = 'ASC';

                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray, $whereMoreString, $groupByColumnBoolean, $justDataBoolean, $numberPerPage, $pageNumber, $orderBy, $order);


                return ['status' => 'success', 'data' => $result];
                break;


            case 'avguser':



                $averageSnapshots = $this->db->select(
                    array('snapshots', 'usersXtargets'),
                    array(
                        '`usersXtargets`.`id`' => '`snapshots`.`usersXtargetsID`',

                    ),
                    array(),
                    array(),
                    array("usersXtargets.gameID", 'COUNT(DISTINCT snapshots.id) AS num_snapshots'),
                    'GROUP BY usersXtargets.gameID',
                    false,
                    false,
                    'all',
                    null,
                    'id',
                    'ASC'
                );

                // Calculate and return the average snapshots per game

                return $this->calculateAverage($averageSnapshots, 'num_snapshots');



                break;
            case 'totalUsers':
                $columnsArray = array(
                    "$totalUsersQuery AS total_Users",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'numberOfUsersInGames':
                $columnsArray = array(
                    "$numberOfUsersInGamesCountQuery AS 'total_users_in_games'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'totalGames':
                $columnsArray = array(
                    "$totalGamesCountQuery AS 'total_games'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'totalActiveGames':
                $columnsArray = array(
                    "$totalActiveGamesCountQuery AS 'total_active_games'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'totalSnapshots':
                $columnsArray = array(
                    "$totalSnapshotsCountQuery AS 'total_snapshots'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'averageUsersPerGame':
                $columnsArray = array(
                    "$averageUsersPerGameQuery AS 'average_users_per_game'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'totalGamesPlayed':
                $columnsArray = array(
                    "$totalGamesPlayedQuery AS 'total_games_played'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'averageHoursPerGame':
                $columnsArray = array(
                    "$averageHoursPerGameQuery AS 'average_hours_per_game'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'averageSnapshotsPerGame':
                $columnsArray = array(
                    "$averageSnapshotsPerGameQuery AS 'average_snapshots_per_game'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'totalHoursOfGameplay':
                $columnsArray = array(
                    "$totalHoursOfGameplayQuery AS 'total_hours_of_gameplay'", //untested
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'totalNewUsersByDay':
                $columnsArray = array(
                    "$totalNewUsersByDayQuery AS 'new_Users_yesterday'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'totalNewUsersByWeek':
                $columnsArray = array(
                    "$totalNewUsersByWeekQuery AS 'new_Users_Last_Week'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'totalNewUsersByMonth':
                $columnsArray = array(
                    "$totalNewUsersByMonthQuery AS 'new_Users_Last_Month'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;
            case 'totalNewUsersByYear':
                $columnsArray = array(
                    "$totalNewUsersByYearQuery AS 'new_Users_Last_Year'",
                );
                $whereArray = array(
                );
                $tables = array("dual");
                $result = $this->db->select($tables, $whereArray, array(), array(), $columnsArray);
                return ['status' => 'success', 'data' => $result];

                break;

            default:
                // Unsupported stat type
                return ['status' => 'error', 'message' => 'Unsupported stat type.'];
        }
    }
    /**
  _______               _____ _        _    
 |__   __|             / ____| |      | |   
    | |_      _____   | (___ | |_ __ _| |_  
    | \ \ /\ / / _ \   \___ \| __/ _` | __| 
    | |\ V  V / (_) |  ____) | || (_| | |_  
    |_| \_/\_/ \___/  |_____/ \__\__,_|\__| 
     
     */
    function fetchTwoStatData($objectName, $id, $secondObjectName)
    {
        // Implement logic to fetch data based on the stat type
        // $this->params has 'secondObjectName', proceed with constructing the query


        // $objectName = $this->params['objectName'];
        // $id = $this->params['id'];
        // $secondObjectName = $this->params['secondObjectName'];
        $whereArray = array();

        if ($objectName == 'games' && $secondObjectName == 'snapshots') { //show me all the snapshots in a game
            // Define your query parameters
            $tables = array('snapshots', 'usersXtargets', 'games');
            //todo we have to create the where array to join tables 

            $columnsArray = array("$secondObjectName.*"); // Assuming you want all columns from the 'snapshots' table

            // Call the select function
            $result = $this->db->select($tables, $whereArray, null, null, $columnsArray);


        } elseif ($objectName == 'users' && $secondObjectName == 'games') { //this should return all the games a user is in
            // Define your query parameters
            $tables = array('games', 'gamesXusers', 'users');
            $whereArray = array(
                '`users`.`id`' => '`gamesXusers`.`userID`',
                '`games`.`id`' => '`gamesXusers`.`gamesID`', //trying to do this ---> JOIN usersXtargets ON games.id = usersXtargets.gameID
                '`gamesXusers`.`userID`' => $id,
            );
            $whereLessThanArray = array(
                'participantTypeID' > 3,

            );

            $columnsArray = array("`gamesXusers`.`gameID`");
            $result = $this->db->select($tables, $whereArray, null, null, $columnsArray);


        }


        // Check the result
        if ($result['status'] === "success") {
            return $result;
        } else {
            // Handle the case where the query was not successful
            return ['error' => $result['message']];
        }

    }
    /** 
            _     _ _ _   _                   _       __                  _   _                 
           | |   | (_) | (_)                 | |     / _|                | | (_)                
   __ _  __| | __| |_| |_ _  ___  _ __   __ _| |    | |_ _   _ _ __   ___| |_ _  ___  _ __  ___ 
  / _` |/ _` |/ _` | | __| |/ _ \| '_ \ / _` | |    |  _| | | | '_ \ / __| __| |/ _ \| '_ \/ __|
 | (_| | (_| | (_| | | |_| | (_) | | | | (_| | |    | | | |_| | | | | (__| |_| | (_) | | | \__ \
  \__,_|\__,_|\__,_|_|\__|_|\___/|_| |_|\__,_|_|    |_|  \__,_|_| |_|\___|\__|_|\___/|_| |_|___/

   */

    function getCount($table, $condition = array())
    {
        $columnsArray = array("COUNT(DISTINCT id) AS count");
        $result = $this->db->select(array($table), $condition, array(), array(), $columnsArray, '', true, true);
        return isset($result['count'][0]) ? $result['count'][0] : 0;
    }



    // Function to get the user with the most hours played
    function getUserWithMostHoursPlayed()
    {
        $columnsArray = array("userID");
        $result = $this->db->select(array("gamesXusers", "games"), array(), array(), array(), $columnsArray, '', true, true);
        return $result['userID'][0];
    }
    // Function to get the user with the most games played
    function getUserWithMostGamesPlayed()
    {
        $columnsArray = array("userID");
        $result = $this->db->select(array("gamesXusers"), array(), array(), array(), $columnsArray, 'COUNT(DISTINCT gameID) DESC', true, true);
        return isset($result['userID'][0]) ? $result['userID'][0] : 0;
    }
    // Function to get the user with the most snapshots taken
    function getUserWithMostSnapshotsTaken()
    {
        $columnsArray = array("userID");
        $result = $this->db->select(array("snapshots"), array(), array(), array(), $columnsArray, 'num_snapshots DESC', true, true);
        return isset($result['userID'][0]) ? $result['userID'][0] : 0;
    }

    // Function to calculate total hours of gameplay
    function getTotalHoursOfGameplay()
    {
        $columnsArray = array("SUM(total_hours_played) AS totalHoursOfGameplay");
        $result = $this->db->select(array("gamesXusers"), array(), array(), array(), $columnsArray, '', true, true);
        return isset($result['totalHoursOfGameplay'][0]) ? $result['totalHoursOfGameplay'][0] : 0;
    }
    function getLargestGameData()
    {
        $tables = array('games', 'gamesXusers u');
        $columns = array('games.id AS gameID', 'COUNT(u.userID) AS numberOfPlayers');
        $where = array('games.id = u.gameID');
        $groupBy = 'games.id';
        $orderBy = 'numberOfPlayers DESC';
        $limit = 1;

        $result = $this->db->select($tables, $where, null, null, $columns, null, true, false, $groupBy, $orderBy, $limit);

        if ($result && isset($result['data'][0])) {
            return [
                'gameID' => $result['data'][0]['gameID'],
                'numberOfPlayers' => $result['data'][0]['numberOfPlayers'],
            ];
        }

        return null;
    }

    function getAllUserIDsforGame($gameID)
    { //searches a specific game id and returns how many players are in it
        $tablesArray = array('gamesXusers');
        $whereArray = array('gameID' => $gameID);
        $response = $this->db->select($tablesArray, $whereArray, array(), array(), array('userID'), '', true, true);
        if (!isset($response['userID']) || empty($response['userID'])) {
            return false;
        } else {
            return $response['userID'];
        }
    }
    function getLargestGameDataOG()
    { //searches all games to find the one with the most players
        // Fetch game data with 'gameID'
        $gameIDS = ["id AS 'gameID'"];
        $gameIDData = $this->db->Select('games', array(), array(), array(), $gameIDS);
        $listOfGames = $gameIDData["data"];
        $gameIDArray = array();

        // Iterate through the array of games
        foreach ($listOfGames as $item) {
            if (isset($item['gameID'])) {
                // Get user IDs for the current game
                $userIDs = $this->getAllUserIDsforGame($item['gameID']);

                // Check if the result is an array
                if (is_array($userIDs)) {
                    $numberOfPlayerscount = count($userIDs);
                } else {
                    $numberOfPlayerscount = 0; // Or any default value you want to set
                }

                // Add 'gameID' and 'numberOfPlayers' to the array
                $gameIDArray[] = [
                    'gameID' => $item['gameID'],
                    'numberOfPlayers' => $numberOfPlayerscount,
                ];
            }
        }

        // Find the array with the highest 'numberOfPlayers'
        $highestNumberOfPlayers = 0;
        $highestNumberOfPlayersGameID = null;

        foreach ($gameIDArray as $gameData) {
            if ($gameData['numberOfPlayers'] > $highestNumberOfPlayers) {
                $highestNumberOfPlayers = $gameData['numberOfPlayers'];
                $highestNumberOfPlayersGameID = $gameData['gameID'];
            }
        }

        // Return the result as an array
        return [
            'gameID' => $highestNumberOfPlayersGameID,
            'numberOfPlayers' => $highestNumberOfPlayers,
        ];
    }

    function calculateAverage($jsonData, $columnName)
    {
        $data = $jsonData;
        $total = 0;

        foreach ($data['data'] as $item) {
            if (isset($item[$columnName])) {
                $total += $item[$columnName];

            }
        }
        $average = $total / count($data['data']);
        return $average;
        //} else {
        //    return "Invalid JSON data format.";
        // }
    }
}







// header("Content-Type: application/json");
// echo json_encode([
//     "Line " . __LINE__ => __FILE__,
//     'function' => debug_backtrace()[1]['function'] ?? 'N/A',
//         "zzReturn" => $response,
// ]);
// die();
// $tables = array('gamesXusers','games');
//                     $columnsArray = array("gameID",'COUNT(DISTINCT userID) AS numberOfPlayers');
//                     $joinArray = array();
//                     $whereArray = array('gamesXusers.gameID' => 'games.id');
//                     $whereMore = 'GROUP BY `gamesXusers`.`gameID`';
//                     $groupByColumnBoolean = false;  // No need for GROUP BY in the main query
//                     $orderBy = '';  // No ordering is necessary for this query
//                     $result = $this->db->select(
//                         $tables,
//                         $whereArray,
//                         array(),
//                         array(),
//                         $columnsArray,
//                         $whereMore,  // Use $whereMoreString for additional WHERE conditions
//                         $groupByColumnBoolean,
//                         false,  // Set $justDataBoolean to false
//                         'all',  // Retrieve all records

//                     );