<?php

use Illuminate\Database\Eloquent\Collection;
use module\Periods;

trait LeadGridTrait
{
    use FormTrait;
    use GridTrait;
    
    public function initGridUrls() {
        $this->data['grid_items_url'] = URL('sale', 'leads', 'items');
        $this->data['grid_item_url'] = URL('sale', 'leads', 'items');
    }
    
    public function initGrid() {
        $this->initGridUrls();
        $this->data['grid'] = $this->getGridUi();
        $this->data['rows'] = App::config('config_admin_limit');
        $this->data['is_ajax'] = $this->request->isAjaxCall();
        
        $from_date = Inputs::get('filter_date_added_start_date');
        if ($from_date) {
            $this->data['from_date_filter'] = date("m/d/Y", strtotime($from_date));
        }
        $end_date = Inputs::get('filter_date_added_start_date');
        if ($end_date) {
            $this->data['to_date_filter'] = date("m/d/Y", strtotime($end_date));
        }

        $this->data['selectors'] = $this->setupEditor(['class' => 'form-inline selectors'], function ($form, $input_classes) {
            
            $editor = [];
            
            $filters_data = $this->getFilterData();
            
            $editor[] = $form->text('Lead name', 'lead_name')
                ->id('lead_name');
            
            $editor[] = $form->text('Phone', 'telephone')
                ->id('telephone')
                ->placeholder('555 555 5555');
            $editor[] = $form->text('E-mail', 'email')
                ->id('email')
                ->placeholder('name@example.com');
                      
            $selected_status = Inputs::get('filter.status_id', []);
            $editor[] = $form->select('Status', 'status_id')
                    ->multiple()
                    ->options(aget($filters_data, 'status_id', []))
                    ->placeholder('Status')
                    ->select($selected_status);

            $editor[] = $form->select('Substatus', 'substatus_id')
                    ->multiple()
                    ->options(aget($filters_data, 'substatus_id', []))
                    ->placeholder('Substatus');

            $selected_company_ids = Inputs::get('filter.company_id', []);
            $editor[] = $form->select('Company', 'company_id')
                    ->select(Inputs::get('filter.company_id', []))
                    ->multiple()
                    ->options(aget($filters_data, 'company_id', []))
                    ->placeholder('Company')
                     ->select($selected_company_ids);

            $editor[] = $form->select('Medium', 'medium_id')
                    ->multiple()
                    ->options(aget($filters_data, 'medium_id', []))
                    ->placeholder('Medium');
            
            $editor[] = $form->select('Source type', 'source_type')
                    ->multiple()
                    ->options(aget($filters_data, 'referral_type', []))
                    ->placeholder('Source type');
            
            $editor[] = $form->hidden('referral_source_id')->id('referral_source_id');
            $editor[] = $form->text('Source', 'source')
                ->id('source')
                ->acomplete('referral_source_id')
                ->resource(URL('customers', 'referral', 'getReferralByKeywordAutocomplete', ['field' => 'filter[referral_source]']));

            $editor[] = $form->hidden('user_id')->id('user_id');
            $editor[] = $form->text('Created by', 'created_by')
                ->id('created_by')
                ->acomplete('user_id')
                ->resource(URL('sale', 'leads', 'findUsers'));

            $user_id = Inputs::get('filter_user_id');
            $user_name = '';
            if ($user_id) {
                $assignee_label = 'No assignee';
                $user = UserModel::find($user_id);
                $user_name = $user->full_name;
            } else {
                $assignee_label = 'or No assignee';
            }
            $editor[] = $form->hidden('commission_user_id')->id('commission_user_id')->value($user_id);
            $editor[] = $form->text('Assignee', 'assignee')
                ->value($user_name)
                ->id('assignee')
                ->acomplete('commission_user_id')
                ->resource(URL('sale', 'leads', 'findUsers'));
            
            $keyword = Inputs::get('keyword');
            if ($keyword) {
                $editor[] = $form->hidden('keyword')->id('keyword')->value($keyword);
            }
            
            $is_no_assignee = Inputs::get('filter.no_assignee') ? 'checked' : '';

            $editor[] = $form->hidden('is_no_assignee')->id('is_no_assignee')->value(Inputs::get('filter.no_assignee'));
            $editor[] = '<div class="form-group checkbox">
                <label>
                    ' . $assignee_label . '
                    <input id="no-assignee" name="filter[no_assignee]" '.$is_no_assignee.' type="checkbox" value="">
                </label>
            </div>';
            
            if (App::hasActionPermission('manage_leads_commission')) {
                $editor[] = $form->select('Paid?', 'paid')
                    ->options([''=>'', 'yes'=>'Yes', 'no'=>'No', 'should_not'=>"Shouldn't be paid" ])
                    ->placeholder('Paid');
            }
            
            $this->data['created_at_uid'] = uid('created_at');
            $created_at_selected = [
                'created_at_period' => Inputs::get('filter_date_added_period'),
                'created_at_from_dummy' => Inputs::get('filter_date_added_start_date'),
                'created_at_to_dummy' => Inputs::get('filter_date_added_end_date')
            ];
            $editor = $editor + HTML::makeDateRangeFilter($form, 'created_at', 'Created at', 'created_at_input', $created_at_selected, aget($this->data, 'created_at_uid'));

            $this->data['status_history_val'] = Inputs::get('status_history');
            
            $editor[] = '<div class="dates">';
            $editor[] = $form->select('Date', 'status_history')
                    ->select(aget($this->data, 'status_history_val', []))
                    ->options([''=>''] + aget($filters_data, 'status_id', []));
            $editor[] = '<div style="margin:0 -10px 0 10px; display:inline-block;">';
            
            $this->data['status_date_uid'] = uid('status_date');
            $status_period = Inputs::get('status_period');
            $status_period_obj = ($status_period != 'custom') ? str_to_period(str_replace('_', ' ', $status_period)) : create_period(Inputs::get('status_date_from'), Inputs::get('status_date_to'));
            $this->data['status_date_selected'] = [
                'status_date_period' => $status_period,
                'status_date_from_dummy' => oget($status_period_obj, 'start'),
                'status_date_to_dummy' => oget($status_period_obj, 'end')
            ];

            $editor = $editor + HTML::makeDateRangeFilter($form, 'status_date', '', 'status_date', aget($this->data, 'status_date_selected'), aget($this->data, 'status_date_uid'));
                        
            $editor[] = '</div>';
            $editor[] = '</div>';
            
            
            $editor[] = '<div class="attributes">
                        <label>Attributes</label>
                    </div>';
            $editor[] = '<div class="buttons-group">';
            $editor[] = $form->button('Filter', 'filter')->class('filter')->attribute('data-toggle', 'tooltip')->title('Filter');
            $editor[] = $form->button('Reset', 'reset')->class('reset')->attribute('data-toggle', 'tooltip')->title('Reset');
            $editor[] = '</div>';

            return $editor;
        });
        
        $this->data['available_attributes'] = $this->getAvailableAttributes(EavModel::ENTITY_LEAD)->toJson(JSON_NUMERIC_CHECK);
        

        $this->template = 'leads/leads_grid.tpl';
        
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render(TRUE));
    }
    
    public function getFilterData() {
        $filter = [];

        $filter['status_id'] = Status::where('group', 'lead_status')
            ->whereNull('parent_status_id')->orderBy('name', 'asc')->lists('name', 'status_id');
        
        $filter['substatus_id'] = Status::parentStatusGroup('lead_status')
            ->joinParentStatus()->with('parentStatus')
            ->orderBy('parent_status_name', 'asc')->orderBy('name', 'asc')
            ->lists('name', 'status_id'); 
        
        $filter['company_id'] = Company::whereIn('id', App::getUserActiveCompanyIds() ?: [0])
            ->orderBy('name', 'asc')->lists('name', 'id');
                    
        $filter['medium_id'] = Status::where('group', 'lead_medium')
            ->orderBy('name', 'asc')->lists('name', 'status_id');
        
        $filter['referral_type'] = ReferralSrcType::orderBy('name', 'asc')->lists('name', 'type_id');
            

        return $filter;
    }

    public function prepareCell($column, $record)
    {
        $content = $record[$column];
        
        switch ($column) {            
            case 'checkbox' :
                $content = "<input type='checkbox' name='selected[]' value='".$record->id."' />";
                break;
            
            case 'fullname':
                $href = URL('sale', 'leads', 'edit', ['lead_id' => $record->id]);                
                $content = HTML::link(null, null, $href, oget($record, 'full_name'), ['target' => '_blank']);
                break;
  
            case 'customer_name' :
                $href = URL('sale', 'customer', 'update', ['customer_id' => $record->customer_id, 'active_tab' => 'tab_actions']);                
                $content = HTML::link(null, null, $href, oget($record->customer, 'full_name'), ['target' => '_blank']);              
                break;

            case 'user_id' :
                $content = oget($record, 'user.full_name');
                break;
            
            case 'date_added' :
                $content = date('m/d/Y H:i', strtotime($record->date_added));
                break;
            
        }

        return $content;
    }
    
    
    public function applyFilter($records, $params)
    {
        $simple_filters = [
            'status_id[]' => 'leads.status_id',
            'substatus_id[]' => 'leads.substatus_id',
            'company_id[]' => 'leads.company_id',
            'medium_id[]' => 'leads.medium_id',
            'source_type[]' => 'mozreferral_src_type.type_id'
        ];
        $status_history = null;
        $status_history_from = null;
        $status_history_to = null;
        foreach (aget($params, 'source', []) as $k => $v) {            
            if(aget($simple_filters, $v['name'])) {
                $records->whereIn(aget($simple_filters, $v['name']), $v['value']);
            }
            
            if ($v['name'] == 'lead_name') {
                $records->where(DB::raw('CONCAT(leads.firstname, " ", leads.lastname)'), 'like', '%'.$v['value'].'%');
            }  
            
            if ($v['name'] == 'telephone') {
                $records->phoneLike($v['value']);
            }
            
            if ($v['name'] == 'email') {
                $records->emailLike($v['value']);
            }
            
            if ($v['name'] == 'referral_source_id') {
                $referral_source_values = explode('|', $v['value']);
                switch ($referral_source_values[0]) {
                    case 'customer':
                        $records->where('mozreferral_src.customer_id', $referral_source_values[1]);
                        break;
                    case 'sales_people':
                        $records->where('mozreferral_src.user_id', $referral_source_values[1]);
                        break;
                    case 'custom':
                        $records->where('mozreferral_src.id', $referral_source_values[1]);
                        break;
                }
            }
            
            if ($v['name'] == 'commission_user_id') {
                $records->whereHas('leadCommissions', function ($query) use ($v) {
                    $query->where('user_id', $v['value']);
                });
            }

            if ($v['name'] == 'user_id') {
                $records->where('leads.user_id', $v['value']);
            }
            
            if ($v['name'] == 'is_no_assignee') {
                $records->has('commission', '<', 1);
            }

            if ($v['name'] == 'commission_user_id') {
                $records->whereHas('commission', function ($q) use ($v) {
                    $q->where('user_id', $v['value']);
                });
            }
            
            if ($v['name'] == 'paid') {
                if ($v['value'] == 'yes') {
                    $records->whereHas('commission', function ($q) {
                        $q->whereNotNull('commission')->where('commission', '!=', '0');
                    });
                }
                if ($v['value'] == 'no') {
                    $records->whereDoesntHave('commission', function ($q) {
                        $q->whereNotNull('commission');
                    });
                }
                if ($v['value'] == 'should_not') {
                    $records->whereHas('commission', function ($q) {
                        $q->where('commission', 0);
                    });
                }
            }

            if ($v['name'] == 'status_history') {
                $status_history = $v['value'];
            }            
            if ($v['name'] == 'status_date_from_dummy') {
                $status_history_from = $v['value'];
            }
            if ($v['name'] == 'status_date_to_dummy') {
                $status_history_to = $v['value'];
            }

            if ($v['name'] == 'created_at_from_dummy') {                
                $from_date = date('Y-m-d 00:00:00', strtotime($v['value']));
                $records->where('leads.date_added', '>=', $from_date);
            }
            if ($v['name'] == 'created_at_to_dummy') {
                $to_date = date('Y-m-d 23:59:59', strtotime($v['value']));
                $records->where('leads.date_added', '<=', $to_date);
            }
            
            if ($v['name'] == 'keyword') {
                $records->findByKeyword($v['value']);
            }
        }
        
        if ($attributes = aget($params, 'attributes')) {
            $records->attributesFilter($attributes);
        }
        
        if ($status_history) {
            $records->whereHas('statusHistory', function ($q) use ($status_history, $status_history_from, $status_history_to) {
                $q->where('entity_type', 'lead')
                    ->where('to_status_id', $status_history);

                if ($status_history_from) {
                    $start = date('Y-m-d 00:00:00', strtotime($status_history_from));
                    $q->where("to_date", '>', $start);
                }

                if ($status_history_to) {
                    $end = date('Y-m-d 23:59:59', strtotime($status_history_to));
                    $q->where("to_date", '<', $end);
                }
            });
        }

        return $records;
    }
}