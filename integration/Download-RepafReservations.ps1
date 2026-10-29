# ============================================================
# Multi-Site REPAF Reservation Incremental Downloader
# KAIA + CBDI
#
# Checks YESTERDAY + TODAY.
# Creates one combined incremental CSV per run.
#
# Final columns:
#   ...REPAF fields, source_site, reservation_id
# ============================================================

$BaseFolder   = "D:\REPAF-Automation"
$TempFolder   = Join-Path $BaseFolder "temp"
$ExportFolder = Join-Path $BaseFolder "exports"
$LogFolder    = Join-Path $BaseFolder "logs"

$ProcessedFile = Join-Path $BaseFolder "processed_ids.txt"

# ------------------------------------------------------------
# Sites
# ------------------------------------------------------------

$Sites = @(
    @{
        Name           = "KAIA"
        BaseUrl        = "https://staging.kaiahomes.com.ph"
        CredentialFile = Join-Path $BaseFolder "kaia-credential.xml"
    },
    @{
        Name           = "CBDI"
        BaseUrl        = "https://staging.cbdi.com.ph"
        CredentialFile = Join-Path $BaseFolder "cbdi-credential.xml"
    }
)

# ------------------------------------------------------------
# Manila date and time
#
# Windows uses "Singapore Standard Time" for UTC+08:00.
# This matches Philippine time.
# ------------------------------------------------------------

$ManilaTimeZone = [System.TimeZoneInfo]::FindSystemTimeZoneById(
    "Singapore Standard Time"
)

$ManilaNow = [System.TimeZoneInfo]::ConvertTimeFromUtc(
    [DateTime]::UtcNow,
    $ManilaTimeZone
)

$Today       = $ManilaNow.ToString("yyyy-MM-dd")
$CurrentTime = $ManilaNow.ToString("HHmm")
$LogDate     = $ManilaNow.ToString("yyyy-MM-dd")

$DatesToCheck = @(
    $ManilaNow.Date.AddDays(-1),
    $ManilaNow.Date
)

# ------------------------------------------------------------
# Create required folders
# ------------------------------------------------------------

$TodayExportFolder = Join-Path $ExportFolder $Today

foreach ($Folder in @(
    $BaseFolder,
    $TempFolder,
    $ExportFolder,
    $LogFolder,
    $TodayExportFolder
)) {
    if (!(Test-Path -LiteralPath $Folder)) {
        New-Item `
            -ItemType Directory `
            -Path $Folder `
            -Force |
            Out-Null
    }
}

# ------------------------------------------------------------
# Logging
# ------------------------------------------------------------

$LogFile = Join-Path $LogFolder "REPAF-$LogDate.log"

function Write-Log {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Message
    )

    $Now = [System.TimeZoneInfo]::ConvertTimeFromUtc(
        [DateTime]::UtcNow,
        $ManilaTimeZone
    )

    $Timestamp = $Now.ToString("yyyy-MM-dd HH:mm:ss")

    "$Timestamp - $Message" |
        Out-File `
            -FilePath $LogFile `
            -Append `
            -Encoding utf8
}


# Extract HTTP status and the WordPress error code without logging credentials
# or reservation contents. Supports Windows PowerShell 5.1 and PowerShell 7.
function Get-DownloadFailure {
    param([System.Management.Automation.ErrorRecord]$Failure)

    $StatusCode = $null
    $WordPressCode = $null
    $ErrorBody = [string]$Failure.ErrorDetails.Message
    $HttpResponse = $Failure.Exception.Response

    if ($null -ne $HttpResponse) {
        try { $StatusCode = [int]$HttpResponse.StatusCode } catch {}

        if ([string]::IsNullOrWhiteSpace($ErrorBody)) {
            try {
                if ($HttpResponse -is [System.Net.HttpWebResponse]) {
                    $Reader = New-Object System.IO.StreamReader($HttpResponse.GetResponseStream())
                    try { $ErrorBody = $Reader.ReadToEnd() } finally { $Reader.Dispose() }
                }
                elseif ($null -ne $HttpResponse.Content) {
                    $ErrorBody = $HttpResponse.Content.ReadAsStringAsync().GetAwaiter().GetResult()
                }
            }
            catch {}
        }
    }

    if (![string]::IsNullOrWhiteSpace($ErrorBody)) {
        try {
            $ErrorObject = $ErrorBody | ConvertFrom-Json -ErrorAction Stop
            $WordPressCode = [string]$ErrorObject.code
        }
        catch {}
    }

    [PSCustomObject]@{
        StatusCode = $StatusCode
        WordPressCode = $WordPressCode
    }
}

Write-Log "----------------------------------------------"
Write-Log "Starting multi-site REPAF processing."

# ------------------------------------------------------------
# Load processed reservation IDs
#
# Format:
# KAIA|12345
# CBDI|12345
#
# Site name is included because KAIA and CBDI can have the
# same numeric WordPress reservation ID.
# ------------------------------------------------------------

$ProcessedIds = @{}

if (Test-Path -LiteralPath $ProcessedFile) {
    Get-Content -LiteralPath $ProcessedFile |
        ForEach-Object {
            $TrackingId = ([string]$_).Trim()

            if (![string]::IsNullOrWhiteSpace($TrackingId)) {
                $ProcessedIds[$TrackingId] = $true
            }
        }
}

Write-Log "Processed ID history loaded: $($ProcessedIds.Count) IDs."

# ------------------------------------------------------------
# New reservations collected from all configured sites
# ------------------------------------------------------------

$AllNewReservations = @()
$NewTrackingIds     = @()
$FailureCount       = 0

# ------------------------------------------------------------
# Process each website
# ------------------------------------------------------------

foreach ($Site in $Sites) {
    $SiteName       = $Site.Name
    $SiteBaseUrl    = $Site.BaseUrl.TrimEnd("/")
    $CredentialFile = $Site.CredentialFile

    Write-Log "----------------------------------------------"
    Write-Log "Processing site: $SiteName"

    # --------------------------------------------------------
    # Load this site's credentials
    # --------------------------------------------------------

    if (!(Test-Path -LiteralPath $CredentialFile)) {
        Write-Log "ERROR: Credential file missing for $SiteName : $CredentialFile"
        $FailureCount++
        continue
    }

    try {
        $Credential = Import-Clixml -LiteralPath $CredentialFile

        $CredentialPair = "{0}:{1}" -f @(
            $Credential.UserName,
            $Credential.GetNetworkCredential().Password
        )

        $BasicToken = [Convert]::ToBase64String(
            [Text.Encoding]::UTF8.GetBytes($CredentialPair)
        )

        $RequestHeaders = @{
            Authorization = "Basic $BasicToken"
            Accept        = "text/csv"
        }
    }
    catch {
        Write-Log "ERROR: Could not load credential for $SiteName."
        Write-Log $_.Exception.Message
        $FailureCount++
        continue
    }

    # --------------------------------------------------------
    # Check yesterday and today
    # --------------------------------------------------------

    foreach ($DateToCheck in $DatesToCheck) {
        $DateString = $DateToCheck.ToString("yyyy-MM-dd")

        $CsvFileName = "$SiteName-repaf-reservations-$DateString.csv"

        $CsvUrl = (
            "$SiteBaseUrl/wp-json/repaf/v1/daily-csv" +
            "?date=$DateString"
        )

        $TempCsv = Join-Path $TempFolder $CsvFileName

        Write-Log "Checking $SiteName date: $DateString"
        Write-Log "URL: $CsvUrl"

        # ----------------------------------------------------
        # Remove any previous temporary copy
        # ----------------------------------------------------

        if (Test-Path -LiteralPath $TempCsv) {
            Remove-Item -LiteralPath $TempCsv -Force
        }

        # ----------------------------------------------------
        # Download with retries
        # ----------------------------------------------------

        $Downloaded = $false

        for ($Attempt = 1; $Attempt -le 3; $Attempt++) {
            try {
                Invoke-WebRequest `
                    -Uri $CsvUrl `
                    -Headers $RequestHeaders `
                    -OutFile $TempCsv `
                    -UseBasicParsing `
                    -TimeoutSec 60 `
                    -ErrorAction Stop

                Write-Log (
                    "$SiteName CSV downloaded successfully " +
                    "for $DateString on attempt $Attempt."
                )

                $Downloaded = $true
                break
            }
            catch {
                Write-Log (
                    "$SiteName download attempt $Attempt failed " +
                    "for $DateString : $($_.Exception.Message)"
                )

                $DownloadFailure = Get-DownloadFailure -Failure $_
                Write-Log ("HTTP status: {0}; WordPress code: {1}" -f @(
                    $DownloadFailure.StatusCode,
                    $DownloadFailure.WordPressCode
                ))

                if ($DownloadFailure.WordPressCode -eq "rest_no_route") {
                    Write-Log ("ERROR: The daily CSV endpoint is not registered on $SiteName. " +
                        "Deploy and activate REPAF plugin 1.6.5 or newer on $SiteBaseUrl, " +
                        "including includes/rest-api.php. Existing CSV files alone do not provide this route.")
                    break
                }

                if ($DownloadFailure.StatusCode -in @(401, 403)) {
                    Write-Log ("ERROR: Check the WordPress Application Password and confirm its user " +
                        "has the manage_repaf_submissions capability on $SiteName.")
                    break
                }

                if ($DownloadFailure.StatusCode -in @(400, 404)) {
                    Write-Log ("ERROR: Check the endpoint response and requested date. " +
                        "This HTTP error will not be retried.")
                    break
                }

                if ($Attempt -lt 3) {
                    Start-Sleep -Seconds 10
                }
            }
        }

        if (!$Downloaded) {
            Write-Log "ERROR: Could not download $SiteName CSV for $DateString."
            $FailureCount++
            continue
        }

        # ----------------------------------------------------
        # Confirm the downloaded file exists and is not empty
        # ----------------------------------------------------

        if (!(Test-Path -LiteralPath $TempCsv)) {
            Write-Log "ERROR: Downloaded CSV was not created: $TempCsv"
            $FailureCount++
            continue
        }

        $DownloadedFile = Get-Item -LiteralPath $TempCsv

        if ($DownloadedFile.Length -eq 0) {
            Write-Log "ERROR: Downloaded CSV is empty for $SiteName on $DateString."
            $FailureCount++
            continue
        }

        # ----------------------------------------------------
        # Import CSV
        #
        # Import-Csv correctly handles quoted values containing
        # commas, including:
        # "Dela Cruz, Juan, Santos, Jr."
        # ----------------------------------------------------

        try {
            $Reservations = @(
                Import-Csv `
                    -LiteralPath $TempCsv `
                    -Encoding UTF8 `
                    -ErrorAction Stop
            )
        }
        catch {
            Write-Log "ERROR: Could not read $SiteName CSV for $DateString."
            Write-Log $_.Exception.Message
            $FailureCount++
            continue
        }

        if ($Reservations.Count -eq 0) {
            Write-Log "$SiteName CSV for $DateString contains no reservations."
            continue
        }

        # ----------------------------------------------------
        # Validate reservation_id
        # ----------------------------------------------------

        $Headers = @(
            $Reservations[0].PSObject.Properties.Name
        )

        if ("reservation_id" -notin $Headers) {
            Write-Log (
                "ERROR: reservation_id column missing in " +
                "$SiteName CSV for $DateString."
            )

            $FailureCount++
            continue
        }

        # ----------------------------------------------------
        # Find reservations not previously exported
        # ----------------------------------------------------

        foreach ($Reservation in $Reservations) {
            $ReservationId = (
                [string]$Reservation.reservation_id
            ).Trim()

            if ([string]::IsNullOrWhiteSpace($ReservationId)) {
                Write-Log (
                    "WARNING: $SiteName reservation without " +
                    "reservation_id skipped."
                )

                $FailureCount++
                continue
            }

            # Website + reservation ID prevents collisions between
            # KAIA and CBDI WordPress record IDs.
            $TrackingId = "$SiteName|$ReservationId"

            if (-not $ProcessedIds.ContainsKey($TrackingId)) {
                # Add the originating site internally.
                # It will be exported as source_site.
                $Reservation |
                    Add-Member `
                        -NotePropertyName "_source_site" `
                        -NotePropertyValue $SiteName `
                        -Force

                $AllNewReservations += $Reservation
                $NewTrackingIds     += $TrackingId

                # Prevent duplicate inclusion during this same run.
                $ProcessedIds[$TrackingId] = $true

                Write-Log "New reservation found: $TrackingId"
            }
        }
    }

    # --------------------------------------------------------
    # Clear credential values from variables
    # --------------------------------------------------------

    $CredentialPair = $null
    $BasicToken      = $null
    $RequestHeaders  = $null
    $Credential      = $null
}

# ------------------------------------------------------------
# Nothing new
# ------------------------------------------------------------

if ($AllNewReservations.Count -eq 0) {
    if ($FailureCount -gt 0) {
        Write-Log ("Multi-site REPAF processing failed with $FailureCount error(s). " +
            "No export was created; downloads did not complete successfully.")
        exit 1
    }

    Write-Log "No new reservations found across all sites."
    Write-Log "Multi-site REPAF processing completed successfully."
    exit 0
}

# ------------------------------------------------------------
# Build final ordered rows
#
# All website CSV fields retain their existing order.
# source_site is placed second-to-last.
# reservation_id is always the final column.
# ------------------------------------------------------------

$RowsForExport = foreach ($Reservation in $AllNewReservations) {
    $OrderedRow = [ordered]@{}

    foreach ($Property in $Reservation.PSObject.Properties) {
        if ($Property.Name -notin @(
            "reservation_id",
            "_source_site"
        )) {
            $OrderedRow[$Property.Name] = $Property.Value
        }
    }

    $OrderedRow["source_site"]    = $Reservation._source_site
    $OrderedRow["reservation_id"] = $Reservation.reservation_id

    [PSCustomObject]$OrderedRow
}

# ------------------------------------------------------------
# Save one combined incremental CSV
# ------------------------------------------------------------

$OutputFileName = "repaf-new-$Today-$CurrentTime.csv"
$OutputFile     = Join-Path $TodayExportFolder $OutputFileName

try {
    $RowsForExport |
        Export-Csv `
            -LiteralPath $OutputFile `
            -NoTypeInformation `
            -Encoding UTF8 `
            -Force

    Write-Log "Created combined incremental CSV: $OutputFile"
    Write-Log "New reservations: $($AllNewReservations.Count)"
}
catch {
    Write-Log "ERROR: Unable to create combined incremental CSV."
    Write-Log $_.Exception.Message
    exit 1
}

# ------------------------------------------------------------
# Update processed IDs only after successful CSV export
# ------------------------------------------------------------

try {
    foreach ($TrackingId in $NewTrackingIds) {
        $TrackingId |
            Out-File `
                -FilePath $ProcessedFile `
                -Append `
                -Encoding utf8
    }

    Write-Log "Processed reservation IDs updated successfully."
}
catch {
    Write-Log "ERROR: Could not update processed ID history."
    Write-Log $_.Exception.Message
    exit 1
}

if ($FailureCount -gt 0) {
    Write-Log ("Exported the downloaded reservations, but processing finished with " +
        "$FailureCount error(s). Check failed sites/dates and rerun after resolving them.")
    exit 1
}

Write-Log "Multi-site REPAF processing completed successfully."
exit 0
