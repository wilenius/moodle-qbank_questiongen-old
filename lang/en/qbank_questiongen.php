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
 * Plugin strings are defined here.
 *
 * @package     qbank_questiongen
 * @category    string
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitylist'] = 'List of activities';
$string['activitylist_help'] = 'Select the activities that should be used to generate questions. You can select multiple activities. <strong>Please note that sending a lot of content can cause high costs. Also note that not all activity types are supported.</strong><br/>
Currently supported are:
<ul>
  <li>Text and media area</li>
  <li>Page</li>
  <li>File (text-based files types, images and PDF)</li>
  <li>Folder (all folder files with text-based file types, images or PDF, including subdirectories)</li>
  <li>Lesson</li>
  <li>Book</li>
</ul>';
$string['addidentifier'] = 'Add a preconfigured prefix ("{$a}") to the question name';
$string['addpreset'] = 'Add preset';
$string['aiidentifiersetting'] = 'Question name prefix';
$string['aiidentifiersettingdesc'] = 'Specify the prefix to be added to the question name when importing to the question bank. Leave it empty to disable the adding of a prefix. The prefix will be just put straight in front of the question name. If you want an additional delimiter (dash, colon, spaces etc.) make sure you add it to the prefix.';
$string['aiidentifiertagsetting'] = 'Question tag';
$string['aiidentifiertagsettingdesc'] = 'Specify the name of the tag that should be added to the question when importing to the question bank. Leave it empty to disable the adding of a tag.';
$string['backtocourse'] = 'Back to course';
$string['category'] = 'Question category';
$string['category_help'] = 'If the category selection is empty, open the question bank for this course once.';
$string['cleanupdelay'] = 'Cleanup delay';
$string['cleanupdelaydesc'] = 'Delay that has to pass before the logs about the generated questions are removed from the table "qbank_questiongen".';
$string['cleanuptask'] = 'Cleanup task for qbank_questiongen';
$string['configurepreset'] = 'Configure preset';
$string['createdquestionssuccess'] = 'Created questions successfully';
$string['createdquestionsuccess'] = 'Created question successfully';
$string['cronoverdue'] = 'The cron task seems not to run,
questions generation rely on AdHoc Tasks that are created by the cron task, please check your cron settings.
See <a href="https://docs.moodle.org/en/Cron#Setting_up_cron_on_your_system">
https://docs.moodle.org/en/Cron#Setting_up_cron_on_your_system
</a> for more information.';
$string['editpreset'] = 'Edit the preset before sending it to the AI';
$string['errorcoursecontentsempty'] = 'The content of the selected activities is empty. Questions cannot be generated.';
$string['errorcreatingquestions'] = 'An error occurred while creating the questions: {$a->failed} out of {$a->total} failed.';
$string['errorcreatingquestionscritical'] = 'An error occurred while creating the questions. Please retry to generate the questions.';
$string['errorformfieldempty'] = 'Field must not be empty';
$string['errorimagetotextnotavailable'] = 'AI based image to text conversion is not available. You cannot use PDF or images as content.';
$string['errornoactivitiesselected'] = 'If you want to use content from the course to generate questions from you have to select at least one activity';
$string['errornogenerateentriesfound'] = 'No entries for generating questions could be found.';
$string['errornotcreated'] = 'Error: questions were not created';
$string['errorpdfnotsupported'] = 'The PDF you were trying to use unfortunately seems to not being compatible: {$a}';
$string['errorstoryempty'] = 'You must provide content to be able to generate questions.';
$string['errortopicempty'] = 'You must provide a topic to be able to generate questions.';
$string['example'] = 'Example';
$string['example_help'] = 'The example shows the AI an example output, to clarify the formatting.';
$string['exception_presetidmissing'] = 'Preset ID missing';
$string['exception_presetnotfound'] = 'Preset with ID {$a} not found';
$string['generate'] = 'Generate questions';
$string['generatemore'] = 'Generate more questions';
$string['generating'] = 'Generating your questions... (You can safely leave this page, and check later on the question bank)';
$string['generationfailed'] = 'The question generation failed after {$a} tries';
$string['gotoquestionbank'] = 'Go to question bank';
$string['instructions'] = 'Instructions';
$string['instructions_help'] = 'The instructions tell the AI what to do.';
$string['linktomanagepresetspage'] = 'Link to presets management page';
$string['managepresets'] = 'Manage global presets';
$string['managepresetswarning'] = 'This link leads you to the global management page of presets. Changes which are performed on this page will affect the whole platform';
$string['mode'] = 'Mode';
$string['mode_help'] = 'Select the mode. You can select between three modes:
<ul>
<li><strong>Topic:</strong> Specify a topic the generated questions should be about. The LLM will be instructed to use its training data to generate the questions from.</li>
<li><strong>Provide content:</strong> Provide the content that the questions should be created from. The LLM will be instructed to not use any data except the one provided. Make sure to provide enough data.</li>
<li><strong>Course contents:</strong> Contents from the current course is being used to create the questions from. You will be able to select what course contents is being sent to the LLM. The LLM will be instructed to not use any data except the one provided. Make sure to provide enough data.</li>
</ul>
';
$string['modecoursecontents'] = 'Course contents';
$string['modestory'] = 'Provide content';
$string['modetopic'] = 'Topic';
$string['name'] = 'Preset name';
$string['numofquestions'] = 'Number of questions to generate';
$string['numoftries'] = '<b>{$a}</b> tries';
$string['numoftriesdesc'] = 'Number of retries that should be performed if generating and import of a question fails';
$string['numoftriesset'] = 'Number of retries';
$string['pluginname'] = 'AI questions generator';
$string['pluginname_desc'] = 'This feature allows you to automatically generate questions from text and course content using a large language model.';
$string['pluginname_help'] = 'Use this plugin from the course administration menu or the question bank.';
$string['preset'] = 'Preset';
$string['presetdeleteconfirm'] = 'Do you really want to delete this preset?';
$string['presetdeleted'] = 'Preset deleted';
$string['presetformat'] = 'Preset format';
$string['presetformatdesc'] = 'Select the format of the example for the LLM to return';
$string['presetinstructions'] = 'Preset instructions';
$string['presetname'] = 'Preset name';
$string['presetnamedesc'] = 'Name that will be shown to the user';
$string['presetprimer'] = 'Preset primer';
$string['presets'] = 'Presets';
$string['presetsaved'] = 'Preset has been saved';
$string['primer'] = 'Primer';
$string['primer_help'] = 'The primer is the first information to be sent to the AI, priming it for its task.';
$string['privacy:metadata'] = 'AI text to questions generator does not store any personal data.';
$string['provider'] = 'LLM provider';
$string['providerdesc'] = 'Select the AI backend you want to use';
$string['questiongen:manage'] = 'Manage questions presets';
$string['questiongeneratingfinished'] = 'All {$a} question generations have been processed.';
$string['questiongeneratingstatus'] = 'Question {$a->current} out of {$a->total} processed.';
$string['sendexistingquestionsascontext'] = 'Send existing questions as context';
$string['sendexistingquestionsascontext_help'] = 'Enable to make the tool send all question titles and question texts from all the questions in the current category to the external AI system to enable the AI system to generate questions that are different from the already existing ones.';
$string['story'] = 'Content';
$string['story_help'] = 'Provide the content the LLM should generate questions from. If this is being used the LLM is being instructed not to use own training data but instead use whatever you insert here. You can also copy/paste whole articles, for example from wikipedia. Make sure you provide enough content to allow the LLM to generate useful questions.';
$string['tasksuccess'] = 'The question generation task was successfully created';
$string['topic'] = 'Topic';
$string['topic_help'] = 'The topic of your questions. Describe the topic you want the LLM to generate questions for.';
$string['waitingforadhoctaskstart'] = 'Waiting for background task to start';
