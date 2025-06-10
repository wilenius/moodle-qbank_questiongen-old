<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     qbank_genai
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Get questions from the API.
 *
 * @param object $dataobject of the stored processing data from genai db table extended with example data.
 * @return object questions of generated questions
 */
function qbank_genai_get_questions($dataobject) {

    // Build primer.
    $primer = $dataobject->primer;
    $primer .= "Write $dataobject->numofquestions questions.";

    $key = get_config('qbank_genai', 'key');

    // Remove new lines and carriage returns.
    $story = str_replace("\n", " ", $dataobject->story);
    $story = str_replace("\r", " ", $story);
    $instructions = str_replace("\n", " ", $dataobject->instructions);
    $instructions = str_replace("\r", " ", $instructions);
    $example = str_replace("\n", " ", $dataobject->example);
    $example = str_replace("\r", " ", $example);

    $messages = [
        [
            "role" => "system",
            "content" => "' . $primer . '",
        ],
        [
            "role" => "system",
            "name" => "example_user",
            "content" => "' . $instructions . '",
        ],
        [
            "role" => "system",
            "name" => "example_assistant",
            "content" => "' . $example . '",
        ],
        [
            "role" => "user",
            "content" => 'Now, create ' . $dataobject->numofquestions
                . ' questions for me based on this topic: "' . qbank_genai_escape_json($story) . '"',
        ]
    ];

    $model = get_config('qbank_genai', 'model');
    $provider = get_config('qbank_genai', 'provider'); // OpenAI (default) or Azure

    $headers = [
            'Content-Type' => 'application/json',
    ];
    if ($provider === 'Azure') {
        // If the provider is Azure, use the Azure API endpoint and Azure-specific HTTP header
        $url = get_config('qbank_genai', 'azure_api_endpoint'); // Use the Azure API endpoint from settings
        $headers['api-key'] = $key;
    } else {
        // If the provider is not Azure, use the OpenAI API URL and OpenAI style HTTP header
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers['Authorization'] = 'Bearer ' . $key;
    }

    $data = json_encode([
        'model' => $model,
        'messages' =>  $messages,
    ]);

    $httpclient = new \core\http_client();
    $options['headers'] = $headers;
    $options['body'] = $data;

    $result = json_decode($httpclient->post($url, $options)->getBody()->getContents());

    $questions = new stdClass(); // The questions object.
    if (isset($result->choices[0]->message->content)) {
        $questions->text = $result->choices[0]->message->content;
        $questions->prompt = $story;
    } else {
        $questions = $result;
        $questions->prompt = $story;
    }

    // Print error message of ChatGPT API (if there are).
    if (isset($questions->error->message)) {
        $error = $questions->error->message;

        // Print error message to cron/adhoc output.
        echo "[qbank_genai] Error : $error.\n";
    }

    return $questions;
}
/**
 * Create questions from data got from ChatGPT output.
 *
 * @param int $category course category
 * @param string $gift questions in GIFT format
 * @param int $numofquestions number of questions to generate
 * @param int $userid user id
 * @param bool $addidentifier add an GPT prefix to question names
 * @return array of objects of created questions
 * @deprecated
 */
function qbank_genai_create_questions($category, $gift, $numofquestions, $userid, $addidentifier) {
    global $CFG, $USER, $DB;

    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot . '/question/format.php');

    // $coursecontext = \context_course::instance($courseid);

    // Get question category TODO: there is probably a better way to do this.
    if ($category) {
        $categoryids = explode(',', $category);
        $categoryid = $categoryids[0];
        $categorycontextid = $categoryids[1];
        $category = $DB->get_record('question_categories', ['id' => $categoryid, 'contextid' => $categorycontextid]);
    }

    // $classname = "\qbank_genai\local\\" . $page->tool;
    // if (!class_exists($classname)) {
    //     throw new \coding_exception('The  ' . $key . ' is not allowed for the purpose ' .
    //         $this->purpose->get_plugin_name());
    // }
    // $toolhelper = new $classname();
    // if (!method_exists($toolhelper, 'hook_after_new_page_created')) {
    //     return "Method 'get_answer_column' is missing in tool helper class " . $page->tool;
    // }

    // \qbank_genai\local\gift::parse_questions()

    // Use existing questions category for quiz or create the defaults.
    // if (!$category) {
    //     $contexts = new core_question\local\bank\question_edit_contexts($coursecontext);
    //     if (!$category = $DB->get_record('question_categories', ['contextid' => $coursecontext->id, 'sortorder' => 999])) {
    //         $category = question_make_default_categories($contexts->all());
    //     }
    // }

    // Split questions based on blank lines.
    // Then loop through each question and create it.

    if ($created) {
        return $createdquestions;
    } else {
        return false;
    }
}

/**
 * Escape json.
 *
 * @param string $value json to escape
 * @return string result escaped json
 */
function qbank_genai_escape_json($value) {
    $escapers = ["\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c"];
    $replacements = ["\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b"];
    $result = str_replace($escapers, $replacements, $value);
    return $result;
}

/**
 * Check if the gift format is valid.
 *
 * @param string $gift questions in GIFT format
 * @return bool true if valid, false if not
 */
function qbank_genai_check_gift($gift) {
    $questions = explode("\n\n", $gift);

    foreach ($questions as $question) {
        $qa = str_replace("\n", "", $question);
        preg_match('/::(.*)\{/', $qa, $matches);
        if (isset($matches[1])) {
            $qlength = strlen($matches[1]);
        } else {
            return false;
            // Error : Question title not found.
        }
        if ($qlength < 10) {
            return false;
            // Error : Question length too short.
        }
        preg_match('/\{(.*)\}/', $qa, $matches);
        if (isset($matches[1])) {
            $wrongs = substr_count($matches[1], "~");
            $right = substr_count($matches[1], "=");
        } else {
            return false;
            // Error : Answers not found.
        }
        if ($wrongs != 3 || $right != 1) {
            return false;
            // Error : There is no single right answers or no 3 wrong answers.
        }
    }
    return true;
}
