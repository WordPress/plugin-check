<script type="text/template" id="tmpl-plugin-check-results-table">
	<table id="plugin-check__results-table-{{data.index}}" class="plugin-check__results-table">
		<thead>
			<tr>
				<td>
					FILE:
				</td>
				<td colspan="4">
					{{ data.file }}
				</td>
			</tr>
			<tr>
				<td>
					Line
				</td>
				<td>
					Column
				</td>
				<td>
					Type
				</td>
				<td>
					Code
				</td>
				<td>
					Message
				</td>
			</tr>
		</thead>
		<tbody id="plugin-check__results-body-{{data.index}}"></tbody>
	</table>
</script>
