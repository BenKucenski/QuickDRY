using System;
using System.Net;
using System.IO;
using System.Text;

/// <summary>
/// Summary description for HTTP
/// </summary>

namespace QuickDRY
{
    public class HTTP
    {
        public HTTP()
        {
        }

        // https://odetocode.com/blogs/scott/archive/2004/10/05/webrequest-and-binary-data.aspx
        public static byte[] LoadURL(String url)
        {
            byte[] result;
            byte[] buffer = new byte[4096];

            WebRequest wr = WebRequest.Create(url);

            try
            {
                using (WebResponse response = wr.GetResponse())
                {
                    using (Stream responseStream = response.GetResponseStream())
                    {
                        using (MemoryStream memoryStream = new MemoryStream())
                        {
                            int count = 0;
                            do
                            {
                                count = responseStream.Read(buffer, 0, buffer.Length);
                                memoryStream.Write(buffer, 0, count);

                            } while (count != 0);

                            result = memoryStream.ToArray();

                        }
                    }
                }
                return result;
            }
            catch (Exception ex)
            {
                return Encoding.ASCII.GetBytes("");
            }
        }

        public static String ContentTypeFromFile(String filename)
        {
            String ContentType;
            ContentType = filename.EndsWith(".css") ? "text/css" : "text/HTML";
            ContentType = filename.EndsWith(".png") ? "image/png" : ContentType;
            ContentType = filename.EndsWith(".js") ? "text/js" : ContentType;
            return ContentType;
        }
    }
}