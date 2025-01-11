<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="UTF-8" indent="yes"/>
    
    <xsl:template match="/previsions">
    <div class="meteo">
        <h1>Prévisions Météo</h1>
        <div class="meteo-echeance">
            <xsl:apply-templates select="echeance[@hour=6 or @hour=12 or @hour=18 or @hour=24]"/>
        </div>
    </div>
    </xsl:template>

    <xsl:template match="echeance">
        <div class="meteo-item">
            <h2 class='hour'>
                <xsl:choose>
                    <xsl:when test="@hour = 6">
                        Matin    
                    </xsl:when>
                    <xsl:when test="@hour = 12">
                        Midi    
                    </xsl:when>                
                    <xsl:when test="@hour = 18">
                        Soir    
                    </xsl:when>
                    <xsl:otherwise>
                        Nuit
                    </xsl:otherwise>
                </xsl:choose>
            </h2>
            <div class="meteo-content">
                <xsl:apply-templates select="temperature/level"/>
                <xsl:apply-templates select="vent_moyen/level"/>
                <div class="ciel">
                    <xsl:choose>
                        <xsl:when test="risque_neige = 'oui'">
                            <xsl:apply-templates select="risque_neige"/>
                        </xsl:when>
                        <xsl:when test="pluie &gt; 0">
                            <xsl:apply-templates select="pluie"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:apply-templates select="nebulosite/level"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </div>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="temperature/level">
        <xsl:if test="@val='2m' and . &gt; -273.15">
            <div class="temperature">
                <xsl:choose>
                    <xsl:when test=". - 273.15 &gt; 30">
                        <i class="fas fa-thermometer-full"></i>
                    </xsl:when>
                    <xsl:when test=". - 273.15 &lt; 0">
                        <i class="fas fa-thermometer-empty"></i>
                    </xsl:when>
                    <xsl:otherwise>
                        <i class="fas fa-thermometer-half"></i>
                    </xsl:otherwise>
                </xsl:choose>
                <p><xsl:value-of select="format-number(. - 273.15, '0')"/> °C</p>
            </div>
        </xsl:if>
    </xsl:template>

    <xsl:template match="vent_moyen/level">
        <xsl:if test="@val='10m' and . &gt; 0">
            <div class="vent">
                <i class="fas fa-wind"></i>
                <p><xsl:value-of select="."/> km/h</p>
            </div>
        </xsl:if>
    </xsl:template>

    <xsl:template match="nebulosite/level">
        <xsl:if test="@val='totale'">
                <xsl:choose>
                    <xsl:when test=". &gt; 50">
                        <i class="fas fa-cloud"></i>
                        <p>Nuageux</p>
                    </xsl:when>
                    <xsl:when test=". &gt; 25">
                        <i class="fas fa-cloud-sun"></i>
                        <p>Partiellement nuageux</p>
                    </xsl:when>
                    <xsl:otherwise>
                        <i class="fas fa-sun"></i>
                        <p>Ensoleillé</p>
                    </xsl:otherwise>
                </xsl:choose>
        </xsl:if>
    </xsl:template>

    <xsl:template match="pluie">
        <i class="fas fa-cloud-rain"></i>
        <p>Pluie : <xsl:value-of select="."/></p>
    </xsl:template>

    <xsl:template match="risque_neige">
        <i class="fas fa-snowflake"></i>
        <p>Risque de neige</p>
    </xsl:template>

</xsl:stylesheet>