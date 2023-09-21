/*
 * Admin Task Menu
 *
 */

function createMonthForm(target, type) {

	let monthRow = document.createElement('div');
	monthRow.className = 'row schedule_forms';
	monthRow.style.cssText = 'margin-bottom: 5px';

	let monthPreTextCol = document.createElement('div');
	monthPreTextCol.className = 'col-2';
	monthPreTextCol.id = 'month_pre_text';
	monthPreTextCol.textContent = '';

	let monthCol = document.createElement('div');
	monthCol.className = 'col-6';

	let monthSelect = document.createElement('select');
	monthSelect.className = 'form-select';
	monthSelect.name = 'month';

	// 1～12月までのoptionタグ作成
	for (let i = 1; i < 13; i++) {
		let monthOption = document.createElement('option');
		monthOption.value = i;
		monthOption.textContent = i;
		monthSelect.appendChild(monthOption);
	}

	let monthTextCol = document.createElement('div');
	monthTextCol.className = 'col-4';
	monthTextCol.id = 'month_text';
	monthTextCol.textContent = 'カ月ごと';

	monthCol.appendChild(monthSelect);
	monthRow.appendChild(monthPreTextCol);
	monthRow.appendChild(monthCol);
	monthRow.appendChild(monthTextCol);
	target.prepend(monthRow);

	let hourPreText = document.getElementById('hour_pre_text');
	let hourText = document.getElementById('hour_text');
	let minutePreText = document.getElementById('minute_pre_text');
	let minuteText = document.getElementById('minute_text');

	// modalならclassとid、取得対象変更
	if (type === 'modal') {
		monthRow.className = 'row modal_schedule_forms';
		monthPreTextCol.id = 'modal_month_pre_text';
		monthTextCol.id = 'modal_month_text';

		hourPreText = document.getElementById('modal_hour_pre_text');
		hourText = document.getElementById('modal_hour_text');
		minutePreText = document.getElementById('modal_minute_pre_text');
		minuteText = document.getElementById('modal_minute_text');
	}

	hourPreText.textContent = '';
	hourText.textContent = '時';
	minutePreText.textContent = 'および';
	minuteText.textContent = '分に開始';
}

function createWeekForm(target, type) {

	let weekRow = document.createElement('div');
	weekRow.className = 'row schedule_forms';
	weekRow.style.cssText = 'margin-bottom: 5px';

	let weekPreTextCol = document.createElement('div');
	weekPreTextCol.className = 'col-2 align-self-center';
	weekPreTextCol.id = 'week_pre_text';
	weekPreTextCol.textContent = '毎週';

	let weekCol = document.createElement('div');
	weekCol.className = 'col-10';

	// 月～木のチェックボックス用row
	let mtRow = document.createElement('div');
	mtRow.className = 'row';

	// 金～日のチェックボックス用row
	let fsRow = document.createElement('div');
	fsRow.className = 'row';

	const dotwArray = {
		'sunday': '日曜日',
		'monday': '月曜日',
		'tuesday': '火曜日',
		'wednesday': '水曜日',
		'thursday': '木曜日',
		'friday': '金曜日',
		'saturday': '土曜日',
	};

	const dotwKeys = Object.keys(dotwArray)
	for (let i = 0; i < dotwKeys.length; i++) {
		let dotwForm = document.createElement('div');
		dotwForm.className = 'form-check col-3';
		dotwForm.style.cssText = 'padding-left: 0';

		let dotwInput = document.createElement('input');
		dotwInput.className = 'form-check-input';
		dotwInput.type = 'checkbox';
		dotwInput.id = dotwKeys[i];
		dotwInput.name = dotwKeys[i];
		dotwInput.value = 1 << i; // 各曜日はシフトで管理
		dotwInput.style.cssText = 'margin: auto; float: none;';

		let dotwLabel = document.createElement('label');
		dotwLabel.className = 'form-check-label';
		dotwLabel.htmlFor = dotwKeys[i];
		dotwLabel.textContent = dotwArray[dotwKeys[i]];

		dotwForm.appendChild(dotwInput);
		dotwForm.appendChild(dotwLabel);
		if (i < 4) {
			mtRow.appendChild(dotwForm);
		} else {
			fsRow.appendChild(dotwForm);
		}
	}

	weekCol.appendChild(mtRow);
	weekCol.appendChild(fsRow);

	weekRow.appendChild(weekPreTextCol);
	weekRow.appendChild(weekCol);
	target.prepend(weekRow);

	// modalならclassとid変更
	let hourPreText = document.getElementById('hour_pre_text');
	let hourText = document.getElementById('hour_text');
	let minutePreText = document.getElementById('minute_pre_text');
	let minuteText = document.getElementById('minute_text');

	// modalならclassとid、取得対象変更
	if (type === 'modal') {
		weekRow.className = 'row modal_schedule_forms';
		weekPreTextCol.id = 'modal_week_pre_text';

		hourPreText = document.getElementById('modal_hour_pre_text');
		hourText = document.getElementById('modal_hour_text');
		minutePreText = document.getElementById('modal_minute_pre_text');
		minuteText = document.getElementById('modal_minute_text');
	}

	hourPreText.textContent = '';
	hourText.textContent = '時';
	minutePreText.textContent = 'および';
	minuteText.textContent = '分に開始';

}

function createDayForm(target, type) {

	let hourPreText = document.getElementById('hour_pre_text');
	let hourText = document.getElementById('hour_text');
	let minutePreText = document.getElementById('minute_pre_text');
	let minuteText = document.getElementById('minute_text');

	// modalなら取得対象変更
	if (type === 'modal') {
		hourPreText = document.getElementById('modal_hour_pre_text');
		hourText = document.getElementById('modal_hour_text');
		minutePreText = document.getElementById('modal_minute_pre_text');
		minuteText = document.getElementById('modal_minute_text');
	}

	hourPreText.textContent = '毎日';
	hourText.textContent = '時';
	minutePreText.textContent = 'および';
	minuteText.textContent = '分に開始';
}

function createHourForm(target, type, min = 0) {

	let hourRow = document.createElement('div');
	hourRow.className = 'row schedule_forms';
	hourRow.style.cssText = 'margin-bottom: 5px';

	let hourPreTextCol = document.createElement('div');
	hourPreTextCol.className = 'col-2';
	hourPreTextCol.id = 'hour_pre_text';
	hourPreTextCol.textContent = '';

	let hourCol = document.createElement('div');
	hourCol.className = 'col-6';

	let hourSelect = document.createElement('select');
	hourSelect.className = 'form-select';
	hourSelect.name = 'hour';

	// min～23時間までのoptionタグ作成
	for (let i = min; i < 24; i++) {
		let hourOption = document.createElement('option');
		hourOption.value = i;
		hourOption.textContent = i;
		hourSelect.appendChild(hourOption);
	}

	let hourTextCol = document.createElement('div');
	hourTextCol.className = 'col-4';
	hourTextCol.id = 'hour_text';
	hourTextCol.textContent = '時間ごと';

	hourCol.appendChild(hourSelect);
	hourRow.appendChild(hourPreTextCol);
	hourRow.appendChild(hourCol);
	hourRow.appendChild(hourTextCol);
	target.prepend(hourRow);

	let minuteText = document.getElementById('minute_text');

	// modalならclassとid変更
	if (type === 'modal') {
		hourRow.className = 'row modal_schedule_forms';
		hourPreTextCol.id = 'modal_hour_pre_text';
		hourTextCol.id = 'modal_hour_text';

		minuteText = document.getElementById('modal_minute_text');
	}

	minuteText.textContent = '分に開始';
}

function createMinuteForm(target, type, min = 0) {

	let minuteRow = document.createElement('div');
	minuteRow.className = 'row schedule_forms';
	minuteRow.style.cssText = 'margin-bottom: 5px';

	let minutePreTextCol = document.createElement('div');
	minutePreTextCol.className = 'col-2';
	minutePreTextCol.id = 'minute_pre_text';
	minutePreTextCol.textContent = '';

	let minuteCol = document.createElement('div');
	minuteCol.className = 'col-6';

	let minuteSelect = document.createElement('select');
	minuteSelect.className = 'form-select';
	minuteSelect.name = 'minute';

	// min～55分までのoptionタグ作成
	for (let i = min; i < 60; i += 5) {
		let minuteOption = document.createElement('option');
		minuteOption.value = i;
		minuteOption.textContent = i;
		minuteSelect.appendChild(minuteOption);
	}

	let minuteTextCol = document.createElement('div');
	minuteTextCol.className = 'col-4';
	minuteTextCol.id = 'minute_text';
	minuteTextCol.textContent = '分ごとに開始';

	minuteCol.appendChild(minuteSelect);
	minuteRow.appendChild(minutePreTextCol);
	minuteRow.appendChild(minuteCol);
	minuteRow.appendChild(minuteTextCol);
	target.prepend(minuteRow);

	// modalならclassとid変更
	if (type === 'modal') {
		minuteRow.className = 'row modal_schedule_forms';
		minutePreTextCol.id = 'modal_minute_pre_text';
		minuteTextCol.id = 'modal_minute_text';
	}
}

// changeイベントが走る度にスケジュール入力フォーム作り直し
function changeScheduleType(event) {
	let id = this.id;
	let targetForm = document.getElementById('schedule_form');
	let removeForm = document.getElementsByClassName('schedule_forms');
	let removeForms = Array.from(removeForm);

	for (let i = 0; i < removeForms.length; i++) {
		targetForm.removeChild(removeForms[i]);
	}

	createForm(id, targetForm, 'front');
}

// modal用changeイベント走る度にスケジュール入力フォーム作り直し
function changeModalScheduleType(event) {
	let targetForm = document.getElementById('modal_schedule_form');
	let removeForm = document.getElementsByClassName('modal_schedule_forms');
	let removeForms = Array.from(removeForm);

	for (let i = 0; i < removeForms.length; i++) {
		targetForm.removeChild(removeForms[i]);
	}

	createForm(this.value, targetForm, 'modal');
}

// form作成
function createForm(id, target, type) {

	switch (id) {
		case 'minute':
			createMinuteForm(target, type, 5);
			break;
		case 'hour':
			createMinuteForm(target, type);
			createHourForm(target, type, 1);
			break;
		case 'day':
			createMinuteForm(target, type);
			createHourForm(target, type);
			createDayForm(target, type);
			break;
		case 'week':
			createMinuteForm(target, type);
			createHourForm(target, type);
			createWeekForm(target, type);
			break;
		case 'month':
			createMinuteForm(target, type);
			createHourForm(target, type);
			createWeekForm(target, type);
			createMonthForm(target, type);
			break;
		case 'custom':
			console.log("カスタム");
			//createCustomForm(targetForm);
			break;
		default:
			break;
	}
}

//追加でスケジュール登録する場合
function addAdditionalSchedule(event) {
	let post_id = this.value;
	let addModalButton = document.getElementById('add_modal');
	addModalButton.value = post_id;
}

// ページ読み込み完了後にイベント追加
window.onload = function () {

	const ev = new Event("change", {
		bubbles: false,
		cancelable: true
	})

	const radio = document.getElementsByClassName('schedule');
	let radios = Array.from(radio);
	for (let i = 0; i < radios.length; i++) {
		radios[i].addEventListener('change', changeScheduleType);
	}
	radios[0].dispatchEvent(ev);

	const modalRadio = document.getElementsByClassName('modal_schedule');
	let modalRadios = Array.from(modalRadio);
	for (let i = 0; i < modalRadios.length; i++) {
		modalRadios[i].addEventListener('change', changeModalScheduleType);
	}
	modalRadios[0].dispatchEvent(ev);

	const add = document.getElementsByClassName('add');
	let adds = Array.from(add);
	for (let i = 0; i < adds.length; i++) {
		adds[i].addEventListener('click', addAdditionalSchedule);
	}
}
