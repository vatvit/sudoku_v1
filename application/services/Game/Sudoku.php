<?php

class Application_Service_Game_Sudoku extends Application_Service_Game_Abstract
{

    const NAME = 'Sudoku game';

    const CODE = 'sudoku';

    const SHUFFLE_COUNT = 20;

    const TOTAL_CELLS = 81;

    protected static $difficulties;

    /**
     * @var array
     */
    protected static $emptyBoard;

    /**
     * @param int $userId
     * @param array $parameters
     * @return Application_Model_Game_Abstract
     */
    public function create($userId, array $parameters = [])
    {
        $difficulty = isset($parameters['difficulty']) ? $parameters['difficulty'] : static::DEFAULT_GAME_DIFFICULTY;
        $difficulty = $this->getDifficulty($difficulty) ?: $this->getDifficulty(static::DEFAULT_GAME_DIFFICULTY);

        $board = $this->generateBoard();
        $board = $this->getOpenCells($board, $difficulty['openCells']);
        $board = $this->normalizeBoardKeys($board);

        $game = [
            'user_id'    => $userId,
            'difficulty' => $difficulty,
            'parameters' => [
                'openCells' => $board,
            ],
            'hash'       => md5($userId . time()),
        ];
        $game = Application_Model_Game_Sudoku::create($game);
        return $game;
    }

    public function load($id)
    {
        $game = Application_Model_Game_Sudoku::load($id);
        return $game;
    }

    public function loadByUserIdAndGameHash($userId, $gameHash)
    {
        $game = Application_Model_Game_Sudoku::loadByUserIdAndGameHash($userId, $gameHash);
        return $game;
    }

    /**
     * @return array
     */
    public function generateBoard()
    {
        $board = $this->getSimpleBoard();
        $board = $this->shuffleBoard($board);
        $board = $this->mergeBoardRows($board);
        return $board;
    }

    /**
     * @return array
     */
    protected function getSimpleBoard()
    {
        $board = [];
        $rows = 9;
        $rowOffsets = [null, 0, 3, 6, 1, 4, 7, 2, 5, 8];
        $row = [1, 2, 3, 4, 5, 6, 7, 8, 9];
        for ($r = 1; $r <= $rows; $r++) {
            $rowCopy = $row;
            $offset = $rowOffsets[$r];
            while ($offset > 0) {
                array_push($rowCopy, array_shift($rowCopy)); // move first element to the end
                $offset--;
            }
            $board[] = $rowCopy;
        }
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function shuffleBoard(array $board)
    {
        $shuffledBoard = $board;
        $allowedMethods = $this->getAllowedShuffleMethods();
        $allowedMethodsCount = count($allowedMethods) - 1;
        $count = self::SHUFFLE_COUNT;
        while ($count > 0) {
            $method = rand(0, $allowedMethodsCount);
            $method = $allowedMethods[$method];
            $method = 'shuffleBoardType' . $method;
            $shuffledBoard = $this->$method($shuffledBoard);
            $count--;
        }
        return $shuffledBoard;
    }

    /**
     * @return array
     */
    public function getAllowedShuffleMethods()
    {
        return [
            'Transposing',
            'SwapRows',
            'SwapCols',
            'SwapSquareRows',
            'SwapSquareCols',
        ];
    }

    /**
     * @param array $board
     * @return mixed
     */
    protected function shuffleBoardTypeTransposing(array $board)
    {
        // I don't understand this magic. Thx Stackoverflow :)
        array_unshift($board, null);
        return call_user_func_array('array_map', $board);
    }

    /**
     * @param array $board
     * @return array
     */
    protected function shuffleBoardTypeSwapRows(array $board)
    {
        $rowToShuffleNumber = rand(1, 9); // 8
        $rowToSwitchOffset = rand(1, 2); // 2
        $squareRow = ceil($rowToShuffleNumber / 3); // 3
        $rowToShufflePosition = $rowToShuffleNumber % 3 ?: 3; // 2 (3 if zero)
        $rowToSwitchPosition = ($rowToShufflePosition + $rowToSwitchOffset) % 3 ?: 3; // ( (2 + 2) % 3 ) = 1 (3 as zero)
        $rowToSwitchNumber = (int)(($squareRow - 1) * 3 + $rowToSwitchPosition); // (3 - 1) * 3 = 6 + 1 = 7
        // So we should switch row 8 and 7
        $rowToShuffleNumber--;
        $rowToSwitchNumber--;
        $tempRow = $board[$rowToShuffleNumber];
        $board[$rowToShuffleNumber] = $board[$rowToSwitchNumber];
        $board[$rowToSwitchNumber] = $tempRow;
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function shuffleBoardTypeSwapCols(array $board)
    {
        $board = $this->shuffleBoardTypeTransposing($board);
        $board = $this->shuffleBoardTypeSwapRows($board);
        $board = $this->shuffleBoardTypeTransposing($board);
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function shuffleBoardTypeSwapSquareRows(array $board)
    {
        $squareToShuffleNumber = rand(1, 3); // 2
        $squareToSwitchOffset = rand(1, 2); // 2
        $squareToSwitchNumber = ($squareToShuffleNumber + $squareToSwitchOffset) % 3 ?: 3; // (2 + 2) % 3 = 1
        // So we should switch square 2 and 1
        $squareToShuffleStart = ($squareToShuffleNumber - 1) * 3; // (2 - 1) * 3 = 3
        $squareToSwitchStart = ($squareToSwitchNumber - 1) * 3; // (1 - 1) * 3 = 0
        $shuffleSquare = array_slice($board, $squareToShuffleStart, 3); // 4..6
        $switchSquare = array_slice($board, $squareToSwitchStart, 3); // 1..3
        array_splice($board, $squareToShuffleStart, 3, $switchSquare);
        array_splice($board, $squareToSwitchStart, 3, $shuffleSquare);
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function shuffleBoardTypeSwapSquareCols(array $board)
    {
        $board = $this->shuffleBoardTypeTransposing($board);
        $board = $this->shuffleBoardTypeSwapSquareRows($board);
        $board = $this->shuffleBoardTypeTransposing($board);
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function mergeBoardRows(array $board)
    {
        $mergedBoard = [];
        foreach ($board as $row) {
            $mergedBoard = array_merge($mergedBoard, $row);
        }
        return $mergedBoard;
    }

    /**
     * @param array $board
     * @param $count
     * @return array
     */
    protected function getOpenCells(array $board, $count)
    {
        if (is_array($count)) {
            $count = rand($count['min'], $count['max']);
        }
        $keys = array_keys($board);
        shuffle($keys);
        $openCells = [];
        while ($count > 0) {
            $key = $keys[$count];
            $openCells[$key] = $board[$key];
            $count--;
        }
        return $openCells;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function normalizeBoardKeys(array $board)
    {
        $normalizedBoard = [];
        foreach ($board as $key => $value) {
            $key++; // 0 -> 1, 8 -> 9, 9 -> 10
            $row = ceil($key / 9);
            $col = $key % 9;
            if (!$col) $col = 9;
            $coords = $row . $col;
            $normalizedBoard[$coords] = $value;
        }
        return $normalizedBoard;
    }

    /**
     * @param array $cells
     * @return array
     */
    public function checkFields(array $cells)
    {
        $errors = [];
        $openCellsPerRows = $openCellsPerCols = $openCellsPerSquares = [];
        foreach ($cells as $coords => $value) {
            list ($row, $col) = str_split($coords);
            $square = (int)((ceil($row / 3) - 1) * 3 + ceil($col / 3));

            if (empty($openCellsPerRows[$row])) {
                $openCellsPerRows[$row] = [];
            }
            $openCellsPerRows[$row][$coords] = $value;

            if (empty($openCellsPerCols[$col])) {
                $openCellsPerCols[$col] = [];
            }
            $openCellsPerCols[$col][$coords] = $value;

            if (empty($openCellsPerSquares[$square])) {
                $openCellsPerSquares[$square] = [];
            }
            $openCellsPerSquares[$square][$coords] = $value;
        }

        $checkCells = function (array $cells) {
            $errors = [];
            foreach ($cells as $data) {
                $exists = [];
                foreach ($data as $coords => $value) {
                    if (isset($exists[$value])) {
                        if (!isset($errors[$exists[$value]])) { // Save first element too
                            $errors[$exists[$value]] = $value;
                        }
                        if (!isset($errors[$coords])) {
                            $errors[$coords] = $value;
                        }
                    }
                    $exists[$value] = $coords;
                }
            }
            return $errors;
        };

        $errors += $checkCells($openCellsPerRows);
        $errors += $checkCells($openCellsPerCols);
        $errors += $checkCells($openCellsPerSquares);
        return $errors;
    }

    /**
     * @param Application_Model_Game_Abstract $game
     * @return array|bool
     */
    public function checkGameSolution(Application_Model_Game_Abstract $game)
    {
        $openCells = (array)$game->getParameter('openCells');
        $checkedCells = (array)$game->getParameter('checkedCells');
        $cells = $openCells + $checkedCells;
        $errors = $this->checkFields($cells);
        if (!empty($errors)) {
            return $errors;
        }
        if (self::TOTAL_CELLS == count($cells)) {
            $game->finish();
            return true;
        }
        return false;
    }

    /**
     * @param string $coords
     * @return bool
     */
    public function checkCoords($coords) {
        list($col, $row) = str_split($coords);
        if ($col >= 1 && $col <= 9) {
            if ($row >= 1 && $row <= 9) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public static function getEmptyBoard()
    {
        if (null === static::$emptyBoard) {
            $board = [];
            $coords = [1, 2, 3, 4, 5, 6, 7, 8, 9];
            foreach ($coords as $r) {
                foreach ($coords as $c) {
                    $board[$r . $c] = '';
                }
            }
            static::$emptyBoard = $board;
        }
        return static::$emptyBoard;
    }

    /**
     * @param int $id
     * @param string $hash
     * @return bool
     */
    public function checkBoard($id, $hash)
    {
        $game = Application_Model_Game_Sudoku::load($id);
        $gameHash = $game->getBoardHash();
        if ($hash === $gameHash) {
            return true;
        }
        return false;
    }

    protected static function initDifficulties()
    {
        parent::initDifficulties();
        $additionalParameters = [
            self::DIFFICULTY_PRACTICE  => ['openCells' => 40],
            self::DIFFICULTY_EASY      => ['openCells' => 35],
            self::DIFFICULTY_NORMAL    => ['openCells' => 30],
            self::DIFFICULTY_EXPERT    => ['openCells' => 25],
            self::DIFFICULTY_NIGHTMARE => ['openCells' => 20],
            self::DIFFICULTY_RANDOM    => ['openCells' => ['min' => 20, 'max' => 30]],
            self::DIFFICULTY_TEST      => ['openCells' => 78],
        ];
        foreach (static::$difficulties as $code => $parameters) {
            if (isset($additionalParameters[$code])) {
                static::$difficulties[$code] += $additionalParameters[$code];
            }
        }
    }

}