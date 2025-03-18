document.addEventListener('DOMContentLoaded', function () {
	let id = JSINFO.id;
	let _id = id;
	let el = document.getElementById(`lm-${_id}`);
	while ((_id.length > 0) && (el === null))
	{
		_id = _id.substring(0,_id.lastIndexOf(':'));
		el = document.getElementById(`lm-${_id}`);
	}
	if (el !== null)
		el.classList.add('current');
	while (id.length > 0)
	{
		let el = document.getElementById(`checkbox-${id}`);
		if (el !== null)
			el.checked = true;
		id = id.substring(0,id.lastIndexOf(':'));
	}
});
