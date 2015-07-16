<?php

/**
 * Class SurveySession
 * IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT
 *
 * If you build caches for the getters, make sure to exclude them from serialization (__sleep).
 * This class is stored in the session and therefore requires some extra care:
 *
 * IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT
 *
 * @property bool $isFinished
 * @property int $surveyId
 * @property string $language
 * @property Survey $survey;
 * @property int $step;
 * @property mixed $format;
 * @property int $maxStep;
 * @property Response $response;
 * @property string $templateDir;
 */
class SurveySession extends CComponent {
    /**
     * These variables are not serialized.
     */
    /**
     * @var Response
     */
    private $_response;
    /**
     * @var Survey
     */
    private $_survey;

    /**
     * These are serialized
     */
    protected $surveyId;
    protected $id;
    protected $responseId;
    protected $finished = false;

    protected $_language = 'en';
    protected $_prevStep = 0;
    protected $_step = 0;
    protected $_maxStep = 0;
    protected $_templateDir;
    protected $_postKey;
    protected $_token;
    /**
     * @param int $surveyId
     * @param int $responseId
     */
    public function __construct($surveyId, $responseId, $id)
    {
        $this->surveyId = $surveyId;
        $this->responseId = $responseId;
        $this->id = $id;
        $this->token = isset($this->response->token) ? $this->response->token : null;
    }

    public function getSurveyId() {
        return $this->surveyId;
    }

    public function getResponseId() {
        return $this->responseId;
    }

    public function getIsFinished() {
        return $this->finished;
    }

    public function getToken() {
        return $this->_token;
    }

    public function setToken($value) {
        $this->_token = $value;
    }
    public function getLanguage() {
        return $this->_language;
    }

    public function setLanguage($value) {
        $this->_language = $value;
    }
    /**
     * Returns the session id for this session.
     * The session id is unique per browser session and does not need to be unguessable.
     * In fact it is just an auto incremented number.
     */
    public function getId() {
        return $this->id;
    }

    public function getResponse() {
        if (!isset($this->_response)) {
            $this->_response = Response::model($this->surveyId)->findByPk($this->responseId);
        }
        return $this->_response;
    }

    /**
     * This function gets the survey active record model for this survey session.
     * @return Survey The survey for this session.
     */
    public function getSurvey() {
        if (!isset($this->_survey)) {
            /** @var Survey $survey */
            \Yii::trace('ok123');
            /**
             * We greedily load questions via groups.
             */
            $this->_survey = $survey = Survey::model()->with([
                'groups' => [
                    'with' => [
                        'questions' => [
                            'with' => [
                                'answers',
                                'questionAttributes'
                            ]
                        ]
                    ]
                ]
            ])->findByPk($this->surveyId);
            /**
             * We manually set the questions in survey to the same objects as those in groups.
             * Note that the $survey->questions relation is redundant.
             */
            $questions = [];
            foreach($survey->groups as $group) {
                foreach($group->questions as $key => $question) {
                    $questions[$key] = $question;
                }
            }

            $survey->questions = $questions;

            /**
             * Manually set the group count.
             */
            $survey->groupCount = count($survey->groups);
        }
        return $this->_survey;
    }

    /**
     * Wrapper function that returns the question given by qid to make sure we always get the same object.
     * @param int $id The primary key of the question.
     * @return Question
     */
    public function getQuestion($id) {

        return isset($this->survey->questions[$id]) ? $this->survey->questions[$id] : null;
    }

    public function getQuestionIndex($id) {
        \Yii::beginProfile(__CLASS__ . "::" . __FUNCTION__);
        $questions = $this->survey->questions;
        $question = $questions[$id];
        $result = array_search($question, array_values($questions), true);
        \Yii::endProfile(__CLASS__ . "::" . __FUNCTION__);
        return $result;
    }

    public function getQuestionByIndex($index) {
        return array_values($this->survey->questions)[$index];
    }

    public function getStepCount() {
        switch ($this->format) {
            case Survey::FORMAT_ALL_IN_ONE:
                $result = 1;
                break;
            case Survey::FORMAT_GROUP:
                $result = count($this->getGroups());
                break;
            case Survey::FORMAT_QUESTION:
                $result = array_sum(array_map(function(QuestionGroup $group) {
                    return count($this->getQuestions($group));
                }));
                break;
            default:
                throw new \Exception("Unknown survey format.");
        }
        return $result;
    }
    /**
     * @return int
     */
    public function getStep() {
        return $this->_step;
    }

    public function setStep($value) {
        if (!is_int($value)) {
            throw new \BadMethodCallException('Parameter $value must be an integer.');
        }
        $this->_step = $value > 0 ? $value : 0;
        $this->_prevStep = $this->_step;
        $this->_maxStep = max($this->_step, $this->_maxStep);
    }


    public function getMaxStep() {
        return $this->_maxStep;
    }

    public function getPrevStep() {
        return $this->_prevStep;
    }

    public function setPrevStep($value) {
        $this->_prevStep = $value;
    }

    public function __sleep() {
        return [
            'surveyId',
            'id',
            '_step',
            '_maxStep',
            '_prevStep',
            'responseId',
            'finished',
            '_language',
            '_postKey',
            '_token'
        ];
    }

    /**
     * Sets the template dir to use for this session.
     * @param string $value
     */
    public function setTemplateDir($value) {
        $this->_templateDir = $value;
    }

    public function getTemplateDir() {
        if (!isset($this->_templateDir)) {
            $this->_templateDir = \Template::getTemplatePath($this->survey->template) . '/';
        };
        return $this->_templateDir;
    }

    public function getGroup($id) {
        return $this->survey->groups[$id];
    }
    public function getGroupIndex($id) {
        \Yii::beginProfile(__CLASS__ . "::" . __FUNCTION__);
        $groups = $this->groups;
        $group = $this->getGroup($id);
        $result = array_search($group, array_values($groups), true);
        \Yii::endProfile(__CLASS__ . "::" . __FUNCTION__);
        return $result;
    }
    /**
     * Returns the list of question groups.
     * Ordered according to the randomization groups.
     * @return QuestionGroup[]
     */
    public function getGroups()
    {
        $groups = $this->survey->groups;

        // Get all randomization groups in order.
        $order = [];
        $randomizationGroups = [];
        foreach ($groups as $group) {
            if (!empty($group->randomization_group)) {
                $order[] = $group->randomization_group;
                $randomizationGroups[$group->randomization_group][] = $group;
            } else {
                $order[] = $group;
            }
        }
        foreach ($order as $i => $group) {
            if (is_string($group)) {
                // Draw a random group from the randomizationGroups array.
                /**
                 * @todo This is not truly random. It would be better to use mt_rand with the response ID as seed
                 * (so it's reproducible. But Suhosin doesn't allow seeding mt_rand.
                 *
                 * Current approach:
                 * Create hash of response id, take last 8 chars (== 4 bytes).
                 */
                $seed = array_values(unpack('L',
                    hex2bin(substr(md5($this->responseId . count($randomizationGroups[$group]), true), -8, 4))))[0];

                $randomIndex = $seed % count($randomizationGroups[$group]);

                $order[$i] = $randomizationGroups[$group][$randomIndex];
                $ids[] = $order[$i]->id;
                unset($randomizationGroups[$group][$randomIndex]);
                $randomizationGroups[$group] = array_values($randomizationGroups[$group]);
            }
        }
        return $order;
    }

    /**
     * Getter for the format. In the future we could allow override per session.
     * @return FORMAT_QUESTION|FORMAT_GROUP|FORMAT_SURVEY;
     */
    public function getFormat() {
        return $this->survey->format;
    }



    /**
     * Returns the questions in group $group, indexed by primary key.
     * @param QuestionGroup $group
     * @return Question[]
     */
    public function getQuestions(QuestionGroup $group) {
        return $group->questions;

        $questions = $group->questions;

        // Get all randomization groups in order.
        $order = [];
        $randomizationGroups = [];
        foreach ($questions as $question) {
            if (empty($question->randomization_group)) {
                $order[] = $question->randomization_group;
                $randomizationGroups[$question->randomization_group][] =$question;
            } else {
                $order[] = $group;
            }
        }
        foreach ($order as $i => $question) {
            if (is_string($question)) {
                // Draw a random question from the randomizationGroups array.
                /**
                 * @todo This is not truly random. It would be better to use mt_rand with the response ID as seed
                 * (so it's reproducible. But Suhosin doesn't allow seeding mt_rand.
                 */
                $seed = array_values(unpack('L',
                    substr(md5($this->responseId . count($randomizationGroups[$question]), true), -4, 4)))[0];

                $randomIndex = $seed % count($randomizationGroups[$question]);

                $order[$i] = $randomizationGroups[$question][$randomIndex];
                $ids[] = $order[$i]->gid;
                unset($randomizationGroups[$question][$randomIndex]);
                $randomizationGroups[$question] = array_values($randomizationGroups[$question]);
            }
        }
        return $order;
    }

    /**
     * This function will be deprecated, for now it is provided as a replacement of direct session access.
     */
    public function getFieldArray() {
        $result = [];
        $fieldMap = createFieldMap($this->surveyId, 'full', true, false, $this->language);
        if (!is_array($fieldMap)) {
            echo "Field map should be an array.";
            var_dump($fieldMap);
            die();
        }
        foreach ($fieldMap as $field)
        {
            if (isset($field['qid']) && $field['qid']!='')
            {
//                $result['fieldnamesInfo'][$field['fieldname']] = $field['sid'].'X'.$field['gid'].'X'.$field['qid'];
//                $result['insertarray'][] = $field['fieldname'];
                //fieldarray ARRAY CONTENTS -
                //            [0]=questions.qid,
                //            [1]=fieldname,
                //            [2]=questions.title,
                //            [3]=questions.question
                //                     [4]=questions.type,
                //            [5]=questions.gid,
                //            [6]=questions.mandatory,
                //            [7]=conditionsexist,
                //            [8]=usedinconditions
                //            [8]=usedinconditions
                //            [9]=used in group.php for question count
                //            [10]=new group id for question in randomization group (GroupbyGroup Mode)

                //JUST IN CASE : PRECAUTION!
                //following variables are set only if $style=="full" in createFieldMap() in common_helper.
                //so, if $style = "short", set some default values here!
                if (isset($field['title']))
                    $title = $field['title'];
                else
                    $title = "";

                if (isset($field['question']))
                    $question = $field['question'];
                else
                    $question = "";

                if (isset($field['mandatory']))
                    $mandatory = $field['mandatory'];
                else
                    $mandatory = 'N';

                if (isset($field['hasconditions']))
                    $hasconditions = $field['hasconditions'];
                else
                    $hasconditions = 'N';

                if (isset($field['usedinconditions']))
                    $usedinconditions = $field['usedinconditions'];
                else
                    $usedinconditions = 'N';
                $result[$field['sid'].'X'.$field['gid'].'X'.$field['qid']]= [
                    intval($field['qid']),
                    $field['sid'].'X'.$field['gid'].'X'.$field['qid'],
                    $title,
                    $question,
                    $field['type'],
                    intval($field['gid']),
                    $mandatory,
                    $hasconditions,
                    $usedinconditions
                ];
                if (isset($field['random_gid']))
                {
                    $result[$field['sid'].'X'.$field['gid'].'X'.$field['qid']][10] = $field['random_gid'];
                }
            }

        }
        return $result;
    }

    public function getPostKey() {
        if (!isset($this->_postKey)) {
            $this->_postKey = \Cake\Utility\Text::uuid();
        }
        return $this->_postKey;
    }
    public function setPostKey($value) {
        $this->_postKey = $value;
    }


}
