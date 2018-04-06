<?php

use module\Periods as Periods;

class ControllerReportSurveyResults extends Controller
{

    use FormTrait;
    use GridTrait;

    protected $rules = [
        'inputs' => [
            'time_from' => 'date_format:H:i',
            'time_to' => 'date_format:H:i',
        ],
    ];
    protected $custom_columns = [
        'response_count', 'survey_name'
    ];

    protected $route = 'report/survey_results';

    public function index()
    {
        $this->document->title = "Survey results";
        $this->document->setMainTitle('Survey results');
        $this->template = 'report/survey_results.tpl';

        $respondent_type = ['' => '', 'customer' => 'Customer', 'lead' => 'Lead'];
        $statuses = ["" => ''] + Status::where('group', 'type_of_communication')->lists('name', 'status_id');
        $companies = App::getUserActiveCompanies()->lists('name', 'id');
        $relation_type = ['' => '', 'call' => 'Call', 'ticket' => 'Ticket'];
        $survey_templates = SurveyTemplate::lists('name', 'id');
        $this->data['grid'] = $this->getGridUi();
        $this->data['rows'] = App::config('config_admin_limit');
        $this->data['validators'] = $this->setupValidators($this->rules, Inputs::all());
        $user_groups = UserGroup::query()->lists('name', 'user_group_id');

        $this->data['filters'] = Inputs::get('filter');
        
        if (!aget($this->data['filters'], 'created_at_period')) {
            $this->data['filters']['created_at_period'] = 'last_month';
            $period = str_to_period('last month');
            
            $this->data['filters']['created_at_from_dummy'] = date("m/d/Y", strtotime($period->start));
            $this->data['filters']['created_at_to_dummy'] = date("m/d/Y", strtotime($period->end));
        } else {
            $from = aget($this->data['filters'], 'created_at_from_dummy');
            $to = aget($this->data['filters'], 'created_at_to_dummy');
            $this->data['filters']['created_at_from_dummy'] = $from ? date("m/d/Y", strtotime($from)) : date("m/d/Y");
            $this->data['filters']['created_at_to_dummy'] = $to ? date("m/d/Y", strtotime($to)) : date("m/d/Y");
        }
        
        $this->data['selectors'] = $this->setupEditor(['class' => 'form-inline'], function ($form, $input_classes) use ($respondent_type, $statuses, $relation_type, $companies, $survey_templates, $user_groups) {

            $survey_response = aget($this->data['filters'], 'survey_response');
            $survey_response_only = false;

            $survey_question = aget($this->data['filters'], 'survey_question');

            if (!$survey_response) {
                $survey_response = aget($this->data['filters'], 'survey_response_only');
                $survey_response_only = true;
            }
            
            $survey_type = aget($this->data['filters'], 'survey_type');

            $type_of_communication = array_search(aget($this->data['filters'], 'type_of_communication'), $statuses);
            $representative_id = aget($this->data['filters'], 'representative_id');
            $representative_fullname = '';
            if (!is_array($representative_id) && $representative = UserModel::find($representative_id)) {
                $representative_fullname = $representative->full_name;
            }

            $company = aget($this->data['filters'], 'company_id');
            if (!$company) {
                $company = array_pluck(App::getUserActiveCompanies(), 'id');
            }
            $survey_name = aget($this->data['filters'], 'survey_name');
            $user_group = aget($this->data['filters'], 'user_group');

            $survey_name_array = array();
            array_push($survey_name_array, aget($this->data['filters'], 'survey_template_id'));
            if (aget($survey_name_array, 0)) {
                $current_id = aget($survey_name_array, 0);
                $related_templates = SurveyTemplate::whereNotNull('parent_id')->get()->keyBy('id')->toArray();
                while ($current_id) {                    
                    $related_surveys = array_filter($related_templates, function ($e) use ($current_id) { return $e['parent_id'] == $current_id; });
                    $survey_child = reset($related_surveys);
                    if ($survey_child) {
                        array_push($survey_name_array, aget($survey_child, 'id'));
                        unset($related_templates[aget($survey_child, 'id')]);
                    } else {
                        $current_id = next($survey_name_array);
                    }
                }                
            }

            $editor = [];

            $editor[] = $form->text('Survey ID', 'id');
            $editor[] = $form->select('Respondent Type', 'respondent_type')->options($respondent_type);
            $editor[] = $form->hidden('respondent_id')->id('respondent_id');

            $editor[] = $form->text('Respondent', 'respondent')
                ->id('respondent')
                ->acomplete('respondent_id')
                ->resource(URL('report', 'survey_results', 'autocompleteAjax', ['field' => 'respondent']));

            $editor[] = $form->select('Company', 'company_id')->options($companies)->multiple('multiple')->select($company);

            $editor[] = $form->select('Survey Name', 'survey_name')
                    ->options($survey_templates)
                    ->multiple('multiple')
                    ->select($survey_name ? $survey_name : $survey_name_array);
            if ($survey_question) {
                $editor[] = $form->text('Survey Question', 'survey_question')->id('survey_question')->value($survey_question);
            }
            $editor[] = $form->text('Survey Response', 'survey_response')->id('survey_response')->value($survey_response);
            
            if ($survey_response_only) {
                $editor[] = $form->hidden('is_survey_response_only')->value(1);
            }
            $editor[] = $form->select('Type of Communication', 'status_id')->options($statuses)->select($type_of_communication);
            $editor[] = $form->select('Relation Type', 'relation_type')->options($relation_type);
            $editor[] = $form->text('Relation ID', 'relation_id');
            
            if ($survey_type) {
                $editor[] = $form->hidden('survey_type')->id('survey_type')->value($survey_type);
            }

            $editor[] = $form->select('User Group', 'user_group')->options($user_groups)->placeholder('User Group')->multiple('multiple')->select($user_group);

            if (is_array($representative_id)) {
                $users_tmp = App::model('survey/survey')->getUsers();
                $users_select = [];
                foreach ($users_tmp as $user) {
                    $users_select[$user['user_id']] = $user['full_name'];
                }
                $editor[] = $form->select('Representative', 'representative_id')
                        ->options($users_select)
                        ->placeholder('Representative')
                        ->multiple('multiple')
                        ->select($representative_id);
                
                $editor[] = "<script> $('#representative_id').multipleSelect(); </script>";

            } else {
                $editor[] = $form->hidden('representative_id')->id('representative_id');
                $editor[] = $form->text('Representative', 'representative')
                        ->id('representative')
                        ->acomplete('representative_id')
                        ->resource(URL('report', 'survey_results', 'autocompleteAjax', ['field' => 'representative']))
                        ->value($representative_fullname);
            }

            // @TODO: Use $this->getFormParts() to get Form object and move form rendering from controller to template

            $editor = array_merge($editor ,HTML::makeDateRangeFilter($form, 'created_at', 'Creation Date', $input_classes, $this->data['filters']));

            $editor[] = $form->text('Time From', 'time_from');
            $editor[] = $form->text('Time To', 'time_to');

            $editor[] = $form->button('Reset', 'reset')->class('btn-primary reset')->attribute('data-toggle', 'tooltip')->title('Reset');

            return $editor;
        });

        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render(TRUE));
    }

    public function getRecords($params = [])
    {
        return Survey::query();
    }

    public function prepareCell($column, $record)
    {
        $content = $record[$column];

        switch ($column) {
            case 'survey_name' :
                $content = $record->getSurveyTemplatesText();
                break;

            case 'response_count' :
                $content = $record->getSurveyQuestionResponsesText();
                break;

            case 'created_at' :
                $content = $record->created_at ? $record->created_at->format('m/d/Y H:i:s') : '';
                break;

            case 'fullname' :
                if (isset($record->lead_id)) {
                    $content = "<a href='" . URL('sale', 'leads', 'edit', ['lead_id' => $record->lead_id]) . "'>" . $record->fullname . "</a>";
                } else if (isset($record->customer_id)) {
                    $content = "<a href='" . URL('sale', 'customer', 'update', ['customer_id' => $record->customer_id]) . "'>" . $record->fullname . "</a>";
                } else {
                    $content = $record->fullname;
                }
                break;

            case 'relation_id' :
                if (isset($record->ticket_id) && $record->ticket_id > 0) {
                    $content = "<a href='" . URL('tickets', 'tickets', 'update', ['ticket_id' => $record->ticket_id]) . "'>" . $record->ticket_id . "</a>";
                } else if (isset($record->call_id) && $record->call_id > 0) {
                    $content = "<a href='" . URL('calls', 'calls', 'index', ['filters[call_id]' => $record->call_id]) . "'>" . $record->call_id . "</a>";
                }
                break;
        }

        return $content;
    }

    public function applyFilter($records, $params)
    {
        $filters = [];
        foreach (aget($params, 'source', []) as $k => $v) {
            $filters[$v['name']] = $v['value'];
        }
        if (isset($filters['company_id[]'])) {
            $filters['company_id'] = $filters['company_id[]'];
        } else {
            $filters['company_id'] = array_pluck(App::getUserActiveCompanies(), 'id');
        }
        if (isset($filters['user_group[]'])) {
            $filters['user_group'] = $filters['user_group[]'];
        }
        if (isset($filters['survey_name[]'])) {
            $filters['survey_name'] = $filters['survey_name[]'];
        }
        if (isset($filters['representative']) && !isset($filters['representative_id'])) {
            $representative_tmp = UserModel::findByKeyWord($filters['representative'])->lists('user_id');
            $filters['representative_id'] = reset($representative_tmp);
        }
        if(isset($filters['time_from']) && !preg_match("/(2[0-3]|[01][0-9]):([0-5][0-9])/", $filters['time_from'])) {
            unset($filters['time_from']);
        }
        if(isset($filters['time_to']) && !preg_match("/(2[0-3]|[01][0-9]):([0-5][0-9])/", $filters['time_to'])) {
            unset($filters['time_to']);
        }

        return Survey::surveysForReport($filters);
    }

    public function autocompleteAjax()
    {
        $field = $this->getInput('field', null) or die('[]');
        $keyword = $this->getInput('keyword', '');
        $limit = App::Config('config_activesearch_limit');

        if ($field == 'respondent') {
            $customer = Survey::searchCustomer($keyword)->limit($limit / 2)->get()->map(function ($survey) {
                return ['value' => 'customer_' . $survey->customer_id, 'label' => $survey->customer->firstname . " " . $survey->customer->lastname];
            });
            $lead = Survey::searchLeads($keyword)->limit($limit / 2)->get()->map(function ($survey) {
                return ['value' => 'lead_' . $survey->lead_id, 'label' => $survey->lead->firstname . " " . $survey->lead->lastname];
            });
            echo json_encode(array_merge($customer->toArray(), $lead->toArray()));

            return;
        }

        if ($field == 'representative') {
             echo UserModel::active()->findByKeyWord($keyword)->get()->sortBy('full_name_or_username')->take($limit)->map( function($user){
                    return  ['value' => $user->user_id, 'label' => "$user->full_name_or_username"];
                })->toJson();

            return;
        }
    }
}