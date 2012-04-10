<!-- $Id: edit_check_list.xsl 8374 2011-12-20 07:45:04Z vator $ -->
<xsl:template match="data" name="view_check_list" xmlns:php="http://php.net/xsl">
<xsl:variable name="date_format">d/m-Y</xsl:variable>

<div id="main_content" class="medium">
	
		<h1>Utførelse av kontroll: <xsl:value-of select="control/title"/></h1>
		<h2>Sjekkliste for: <xsl:value-of select="location_array/loc1_name"/></h2>
		
		<xsl:call-template name="check_list_tab_menu" />
	
		<!-- =======================  MESSAGE DETAILS  ========================= -->
		<h3 class="box_header">Meldingen gjelder</h3>
		<div class="box">
			<div class="row"><label>Kontroll:</label> <xsl:value-of select="control/title"/></div>
			<div class="row"><label>Utført dato:</label>
				<xsl:choose>
					<xsl:when test="check_list/completed_date != 0">
						<xsl:value-of select="php:function('date', $date_format, number(check_list/completed_date))"/>
					</xsl:when>
					<xsl:otherwise>
						 Ikke utført
					</xsl:otherwise>
				</xsl:choose>
			</div>
			<div class="row">
			<xsl:choose>
				<xsl:when test="buildings_array/child::node()">
					<select id="building_id" name="building_id">
							<option value="0">
								Velg bygning
							</option>
							<xsl:for-each select="buildings_array">
								<option value="{id}">
									<xsl:value-of disable-output-escaping="yes" select="name"/>
								</option>
							</xsl:for-each>
						</select>
				</xsl:when>
				<xsl:otherwise>
					<label>Bygg:</label> <xsl:value-of select="building/loc1_name"/>	
				</xsl:otherwise>
			</xsl:choose>
			</div>
		</div>
		
		<!-- =======================  MESSAGE DETAILS  ========================= -->
		<h3 class="box_header">Detaljer for meldingen</h3>
		<div class="box">
			<xsl:choose>
				<xsl:when test="check_items_and_cases/child::node()">
					
				<form id="frmRegCaseMessage" action="index.php?menuaction=controller.uicase.register_case_message" method="post">
					<input>
				      <xsl:attribute name="name">check_list_id</xsl:attribute>
				      <xsl:attribute name="type">hidden</xsl:attribute>
				      <xsl:attribute name="value">
				      	<xsl:value-of select="check_list/id"/>
				      </xsl:attribute>
				    </input>
				    <!-- === TITLE === -->
				    <div class="row">
						<label>Tittel på melding:</label>
						<input name="message_title" type="text" />
					</div>
					<!-- === CATEGORY === -->
					<div class="row">
						<label>Kategori:</label>
						 <select name="message_cat_id">
						 	<option value="0">Velg kategori</option>
							<xsl:for-each select="categories/cat_list">
								<xsl:variable name="cat_id"><xsl:value-of select="./cat_id"/></xsl:variable>
								<option value="{$cat_id}">
									<xsl:value-of select="./name"/>
								</option>			
							</xsl:for-each>
						</select>
					</div>
					<!-- === UPLOAD FILE === -->
					<div class="row">
						<label>Filvedlegg:</label>
						<input type="file" id="file" name="file" />
					</div>
			
					<h3>Velg sjekkpunkter som skal være med i melding</h3>					
					<ul class="cases">
						<xsl:for-each select="check_items_and_cases">
							<xsl:choose>
							 	<xsl:when test="cases_array/child::node()">
							 		<li>
								 		<h4><span><xsl:value-of select="control_item/title"/></span></h4>
								 		<ul>		
											<xsl:for-each select="cases_array">
												<xsl:variable name="cases_id"><xsl:value-of select="id"/></xsl:variable>
												<li style="list-style:none;"><input type="checkbox"  name="case_ids[]" value="{$cases_id}" /><xsl:value-of select="descr"/></li>
											</xsl:for-each>
										</ul>
							 		</li>
							 	</xsl:when>
						 	</xsl:choose>
						</xsl:for-each>
					</ul>
					
					<div class="form-buttons">
						<xsl:variable name="lang_save"><xsl:value-of select="php:function('lang', 'save')" /></xsl:variable>
						<input class="btn focus" type="submit" name="save_control" value="Send melding" title="{$lang_save}" />
					</div>
				</form>			
				</xsl:when>
				<xsl:otherwise>
					Ingen registrerte saker
				</xsl:otherwise>
			</xsl:choose>
		</div>
			
</div>
</xsl:template>
