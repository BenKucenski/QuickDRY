<style>
@page {
	margin-top: 0.5in;
	marin-bottom: 0.5in;
	margin-left: 0.5in;
	margin-right: 0.5in;
}

body {
	font-family: helvetica;
	font-size: 11pt;
}

table.pdf_report {
	width: 100%;
	border: 0.25pt solid #669E00;
	border-collapse: collapse;
}


table.pdf_report td {
	text-align: right;
}

table.pdf_report tr:nth-child(even) {background: #eee}
table.pdf_report tr:nth-child(odd) {background: #FFF}

table.pdf_report thead tr th {
	font-weight: bold;
	text-align: center;
	border: solid 0.25pt #669E00;
	padding: 0.25em;
	white-space: nowrap;
}

table.pdf_report tr td.header {
	font-weight: bold;
	text-align: center;
	border: solid 1px #416600;
	background: #ddd;
}

table.pdf_report tr td {
	border-left: 0.25pt solid #669E00;
	border-right: 0.25pt solid #669E00;
	padding: 0.25em;
}

#header {
	position: fixed;
	left: 0;
	right: 0;
	background-color: #fff;
}

#footer {
	position: fixed;
	left: 0;
	bottom: 0;
	right: 0;
	height: 0;
	background-color: #fff;
}

.pagenum:after { content: counter(page); }

.below_optimum {
	background-color: #600;
}

.above_optimum {
	background-color: #060;
}

</style>